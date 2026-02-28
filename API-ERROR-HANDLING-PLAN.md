# GitHub API Client Error Handling Improvement Plan

## Goal

Improve error handling in GithubApiClient to expose detailed error messages from GitHub API responses, eliminating the need for application-level workarounds.

## Current State

**Problem:** All API error responses lose detailed error information from GitHub.

**Current Pattern (appears ~20+ times):**
```php
if ($response->getStatusCode() === 200) {
    $data = json_decode((string) $response->getBody());
    return SomeObject::fromApiResponse($data);
} else {
    throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
}
```

**What Users See:**
```
❌ Failed: 422 Unprocessable Entity
❌ Failed: 404 Not Found
❌ Failed: 403 Forbidden
```

**What GitHub Actually Returns:**
```json
{
  "message": "Unprocessable Entity",
  "errors": [
    "Review Can not approve your own pull request"
  ],
  "documentation_url": "https://docs.github.com/..."
}
```

## Proposed Solution

### 1. Create Centralized Error Parsing Helper

**New Method in GithubApiClient:**

```php
/**
 * Parse GitHub API error response and create detailed exception message
 *
 * @param ResponseInterface $response The HTTP response
 * @return string Detailed error message
 */
private function parseErrorResponse(ResponseInterface $response): string
{
    $statusCode = $response->getStatusCode();
    $reasonPhrase = $response->getReasonPhrase();
    $baseMessage = "{$statusCode} {$reasonPhrase}";

    try {
        $body = (string) $response->getBody();
        $errorData = json_decode($body);

        if (!$errorData) {
            // Not JSON or invalid JSON
            return $baseMessage;
        }

        $details = [];

        // GitHub provides detailed errors in an array
        if (isset($errorData->errors) && is_array($errorData->errors)) {
            foreach ($errorData->errors as $error) {
                if (is_string($error)) {
                    $details[] = $error;
                } elseif (is_object($error) && isset($error->message)) {
                    $details[] = $error->message;
                }
            }
        }

        // Fallback to message field
        if (empty($details) && isset($errorData->message) && $errorData->message !== $reasonPhrase) {
            $details[] = $errorData->message;
        }

        if (!empty($details)) {
            return $baseMessage . ': ' . implode('; ', $details);
        }

        return $baseMessage;
    } catch (\Exception $e) {
        // If anything goes wrong parsing, return base message
        return $baseMessage;
    }
}
```

### 2. Update All Error Throws to Use Helper

**Pattern to Replace:**

```php
} else {
    throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
}
```

**Replace With:**

```php
} else {
    throw new Exception($this->parseErrorResponse($response));
}
```

**Locations (approximate count: 20+ methods):**

- `createReview()` - Line 395
- `getCombinedStatus()` - Line 422
- `listCheckRuns()` - Line 455
- `listIssueLabels()` - Line ~480
- `listPullRequests()` - Similar pattern
- `getPullRequest()` - Similar pattern
- `createPullRequest()` - Similar pattern
- `updatePullRequest()` - Similar pattern
- `mergePullRequest()` - Similar pattern
- `closePullRequest()` - Similar pattern
- `reopenPullRequest()` - Similar pattern
- `createRelease()` - Similar pattern
- `updateRelease()` - Similar pattern
- `deleteRelease()` - Similar pattern
- `uploadReleaseAsset()` - Similar pattern
- `addLabels()` - Similar pattern
- `removeLabel()` - Similar pattern
- ... and more

### 3. Add getCurrentUser() Method

**New Public Method:**

```php
/**
 * Get the authenticated user
 *
 * @return GithubUser The authenticated user
 * @throws Exception If the request fails
 */
public function getCurrentUser(): GithubUser
{
    $request = $this->requestFactory->createRequest(
        'GET',
        $this->config->getApiUrl() . '/user'
    )->withHeader('Authorization', 'Bearer ' . $this->config->getToken())
     ->withHeader('Accept', 'application/vnd.github+json')
     ->withHeader('X-GitHub-Api-Version', '2022-11-28');

    $response = $this->httpClient->sendRequest($request);

    if ($response->getStatusCode() === 200) {
        $data = json_decode((string) $response->getBody());
        return GithubUser::fromApiResponse($data);
    } else {
        throw new Exception($this->parseErrorResponse($response));
    }
}
```

**Benefits:**
- Eliminates need for `gh` CLI dependency in horde-components
- Proper API integration
- Can cache user info to avoid repeated API calls

## Implementation Steps

### Phase 1: Add parseErrorResponse() Helper
1. Add private method to GithubApiClient
2. Add unit tests for various error response formats
3. Verify it handles all GitHub error response patterns

### Phase 2: Systematic Replacement
1. Find all `throw new Exception($response->getStatusCode()...)` patterns
2. Replace with `throw new Exception($this->parseErrorResponse($response))`
3. Use search/replace with verification

### Phase 3: Add getCurrentUser()
1. Add public method
2. Add unit test
3. Update horde-components to use it instead of `gh` CLI

### Phase 4: Testing
1. Test with various error scenarios (403, 404, 422, etc.)
2. Verify error messages are helpful
3. Ensure backward compatibility (no breaking changes)

## Error Response Formats from GitHub

### Format 1: Array of Strings
```json
{
  "message": "Unprocessable Entity",
  "errors": [
    "Review Can not approve your own pull request"
  ]
}
```

### Format 2: Array of Objects
```json
{
  "message": "Validation Failed",
  "errors": [
    {
      "resource": "PullRequest",
      "code": "invalid",
      "field": "title",
      "message": "title is too long (maximum is 256 characters)"
    }
  ]
}
```

### Format 3: Simple Message
```json
{
  "message": "Not Found",
  "documentation_url": "https://docs.github.com/..."
}
```

### Format 4: Rate Limit
```json
{
  "message": "API rate limit exceeded",
  "documentation_url": "https://docs.github.com/..."
}
```

## Expected Improvements

### Before
```
❌ 422 Unprocessable Entity
❌ 404 Not Found
❌ 403 Forbidden
❌ 400 Bad Request
```

### After
```
❌ 422 Unprocessable Entity: Review Can not approve your own pull request
❌ 404 Not Found: Repository not found
❌ 403 Forbidden: Resource not accessible by personal access token
❌ 400 Bad Request: title is too long (maximum is 256 characters)
```

## Files to Modify

**Core:**
- `src/GithubApiClient.php` - Add parseErrorResponse(), update all error throws

**Optional:**
- Add unit tests for error parsing
- Update existing tests if they check exact error messages

## Backward Compatibility

✅ **Fully backward compatible**
- Exception type unchanged (still `\Exception`)
- Exception messages enhanced (more detail, not less)
- No changes to method signatures
- No changes to return types
- Callers that catch exceptions will get better error messages automatically

## Testing Strategy

1. **Unit tests** for parseErrorResponse():
   - Array of strings
   - Array of objects
   - Simple message
   - Invalid JSON
   - Empty response
   - Edge cases

2. **Integration tests** with mock responses:
   - 422 with detailed errors
   - 404 with message
   - 403 with message
   - 400 with validation errors

3. **Manual testing** with real API:
   - Try to approve own PR (422)
   - Access non-existent repo (404)
   - Invalid token (401)
   - Rate limit exceeded (403)

## Rollout Plan

1. Create feature branch: `feature/improve-error-messages`
2. Implement parseErrorResponse() with tests
3. Update all error throws systematically
4. Add getCurrentUser() method
5. Run full test suite
6. Create PR for review
7. Merge to FRAMEWORK_6_0
8. Update horde-components to use getCurrentUser()

## Success Criteria

✅ All API errors include detailed GitHub error messages
✅ Users see actionable error information
✅ No breaking changes to public API
✅ All existing tests pass
✅ New unit tests cover error parsing
✅ getCurrentUser() method available for use in horde-components

## Estimated Impact

**Files to modify:** 1 main file (GithubApiClient.php)
**Lines to change:** ~50-60 error throw statements
**New lines:** ~40 (parseErrorResponse method + tests)
**Test coverage:** Add ~5-10 test cases
**Breaking changes:** None
**Migration needed:** None (automatic improvement for all callers)
