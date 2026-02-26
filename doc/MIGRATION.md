# Migration Guide

## Upgrading to Enhanced API Version

This guide helps you upgrade from the basic GitHub API client to the enhanced version with comprehensive pull request management capabilities.

## What's New

The enhanced version adds extensive pull request management features:

- **Pull Request Operations**: Get, update, merge, and close pull requests
- **Comment Management**: Create, read, update, and delete PR comments
- **Review Management**: List reviews and request reviewers
- **Status Checks**: Monitor CI/CD pipeline status and check runs
- **Label Management**: Add, remove, and set labels on issues and PRs
- **Enhanced Data**: Pull requests now include many additional fields

## Breaking Changes

### StreamFactory Now Optional But Recommended

**Previous:**
```php
$client = new GithubApiClient($httpClient, $requestFactory, $config);
```

**Now (recommended):**
```php
$client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
```

**Impact:** Methods that create or update resources (POST/PUT/PATCH) require a StreamFactory:
- `updatePullRequest()`
- `createPullRequestComment()`
- `updateComment()`
- `requestReviewers()`
- `addLabels()`
- `setLabels()`
- `mergePullRequest()`

If you attempt to use these methods without providing a StreamFactory, you'll get an exception with a clear message.

**Action Required:** Pass a PSR-17 StreamFactory implementation to the constructor if you need write operations.

### Enhanced GithubPullRequest Object

The `GithubPullRequest` object now has many additional properties that may be null in older API responses:

**New Properties:**
- `?string $body` - PR description
- `bool $draft` - Is draft PR
- `bool $merged` - Is merged
- `?string $mergedAt` - Merge timestamp
- `string $createdAt` - Creation timestamp
- `string $updatedAt` - Update timestamp
- `string $headRef` - Head branch
- `string $baseRef` - Base branch
- `string $headSha` - Head commit SHA
- `?GithubUser $author` - PR author
- `array<GithubLabel> $labels` - Labels
- `array<GithubUser> $requestedReviewers` - Reviewers
- `?bool $mergeable` - Mergeable flag
- `?string $mergeableState` - Mergeable state

**Impact:** If you're serializing or storing PR objects, you may need to handle these new fields.

**Action Required:** Review code that processes `GithubPullRequest` objects. Most code should continue working as the core properties (`number`, `title`, `state`, `htmlUrl`) remain unchanged.

## New Capabilities

### 1. Get Detailed Pull Request Information

```php
// New method - get complete PR details
$pr = $client->getPullRequest($repo, 123);

// Now you can access many more fields
echo "Author: {$pr->author->login}\n";
echo "Created: {$pr->createdAt}\n";
echo "Mergeable: " . ($pr->mergeable ? 'Yes' : 'No') . "\n";
echo "Draft: " . ($pr->draft ? 'Yes' : 'No') . "\n";
```

### 2. Update Pull Requests

```php
use Horde\GithubApiClient\PullRequestUpdate;

// Update title
$update = new PullRequestUpdate(title: 'New Title');
$client->updatePullRequest($repo, 123, $update);

// Update multiple fields
$update = new PullRequestUpdate(
    title: 'New Title',
    body: 'Updated description',
    base: 'develop'
);
$client->updatePullRequest($repo, 123, $update);

// Close a PR
$client->closePullRequest($repo, 123);
```

### 3. Manage Comments

```php
// List comments
$comments = $client->listPullRequestComments($repo, 123);

// Create comment
$comment = $client->createPullRequestComment($repo, 123, 'Great work!');

// Update comment
$client->updateComment($repo, $comment->id, 'Even better work!');

// Delete comment
$client->deleteComment($repo, $comment->id);
```

### 4. Work with Reviews

```php
// List reviews
$reviews = $client->listPullRequestReviews($repo, 123);
foreach ($reviews as $review) {
    echo "{$review->user->login}: {$review->state}\n";
}

// Request reviewers
$client->requestReviewers($repo, 123, ['username1', 'username2']);
```

### 5. Monitor CI/CD Status

```php
// Get combined status
$status = $client->getCombinedStatus($repo, 'main');
echo "Status: {$status->state}\n";

// List check runs
$checkRuns = $client->listCheckRuns($repo, 'feature-branch');
foreach ($checkRuns as $run) {
    echo "{$run->name}: {$run->conclusion}\n";
}
```

### 6. Manage Labels

```php
// List labels
$labels = $client->listIssueLabels($repo, 123);

// Add labels
$client->addLabels($repo, 123, ['bug', 'priority-high']);

// Replace all labels
$client->setLabels($repo, 123, ['bug', 'in-progress']);

// Remove a label
$client->removeLabel($repo, 123, 'wontfix');
```

### 7. Merge Pull Requests

```php
use Horde\GithubApiClient\MergePullRequestParams;

// Simple merge
$params = new MergePullRequestParams();
$result = $client->mergePullRequest($repo, 123, $params);

// Squash merge with custom message
$params = new MergePullRequestParams(
    commitTitle: 'feat: add feature X',
    commitMessage: 'Implements feature X\n\nCloses #123',
    mergeMethod: 'squash'
);
$result = $client->mergePullRequest($repo, 123, $params);

if ($result->merged) {
    echo "Successfully merged: {$result->sha}\n";
}
```

## Gradual Migration Strategy

You can adopt the new features gradually:

### Phase 1: Update Constructor (Optional)
Add StreamFactory to enable write operations:
```php
$client = new GithubApiClient(
    $httpClient,
    $requestFactory,
    $config,
    $streamFactory  // Add this
);
```

### Phase 2: Use New Read Operations
Start using new read-only methods that don't require StreamFactory:
- `getPullRequest()`
- `listPullRequestComments()`
- `listPullRequestReviews()`
- `getCombinedStatus()`
- `listCheckRuns()`
- `listIssueLabels()`

### Phase 3: Add Write Operations
Once StreamFactory is added, use write operations:
- `updatePullRequest()`
- `createPullRequestComment()`
- `requestReviewers()`
- `addLabels()`
- `mergePullRequest()`

## Testing Your Upgrade

1. **Add StreamFactory:**
   ```php
   use Horde\Http\StreamFactory;
   $streamFactory = new StreamFactory();
   ```

2. **Update client instantiation:**
   ```php
   $client = new GithubApiClient(
       $httpClient,
       $requestFactory,
       $config,
       $streamFactory
   );
   ```

3. **Test basic operations still work:**
   ```php
   // Existing functionality should still work
   $repos = $client->listRepositoriesInOrganization($org);
   $pullRequests = $client->listPullRequests($repo);
   ```

4. **Try new features:**
   ```php
   // New functionality
   $pr = $client->getPullRequest($repo, 1);
   $comments = $client->listPullRequestComments($repo, 1);
   ```

## Backwards Compatibility

The enhanced version maintains backwards compatibility for:
- All existing method signatures
- Core `GithubPullRequest` properties (`number`, `title`, `state`, `htmlUrl`)
- Repository listing functionality
- Basic pull request listing

The only change that may require code updates is adding the StreamFactory parameter if you want to use write operations.

## Getting Help

- See [README.md](README.md) for comprehensive usage examples
- See [API.md](API.md) for complete API reference
- Run `bin/demo-client.php` to see working examples

## Troubleshooting

### "StreamFactory is required" Exception

**Problem:** Trying to use a write operation without StreamFactory.

**Solution:** Add StreamFactory to the constructor:
```php
use Horde\Http\StreamFactory;
$client = new GithubApiClient(
    $httpClient,
    $requestFactory,
    $config,
    new StreamFactory()  // Add this
);
```

### Unexpected Properties on GithubPullRequest

**Problem:** Code breaks when encountering new PR properties.

**Solution:** The new properties are additions, not changes. Ensure your code only accesses the properties it needs and handles null values appropriately:
```php
// Safe
$title = $pr->title;  // Always present

// Also safe
$author = $pr->author?->login ?? 'Unknown';  // Nullable
```

### Missing Type Declarations

**Problem:** Type errors when passing parameters.

**Solution:** Use the correct types:
- Repository: `GithubRepository` object
- Update parameters: `PullRequestUpdate` DTO
- Merge parameters: `MergePullRequestParams` DTO
- Numbers: `int` (not string)
- Labels: `array<string>` (not comma-separated string)
