# GitHub API Client - API Reference

## Table of Contents

- [GithubApiClient](#githubapiclient)
- [Configuration](#configuration)
- [Value Objects](#value-objects)
- [Collections](#collections)
- [DTOs](#dtos)

## GithubApiClient

Main client class for interacting with the GitHub API.

### Constructor

```php
public function __construct(
    ClientInterface $httpClient,
    RequestFactoryInterface $requestFactory,
    GithubApiConfig $config,
    ?StreamFactoryInterface $streamFactory = null
)
```

**Parameters:**
- `$httpClient` - PSR-18 HTTP client
- `$requestFactory` - PSR-17 request factory
- `$config` - GitHub API configuration (contains access token)
- `$streamFactory` - PSR-17 stream factory (required for POST/PUT/PATCH operations)

### Repository Methods

#### listRepositoriesInOrganization()

List all repositories in an organization.

```php
public function listRepositoriesInOrganization(
    GithubOrganizationId $org
): GithubRepositoryList
```

**Returns:** Collection of repositories

### Pull Request Methods

#### listPullRequests()

List pull requests in a repository.

```php
public function listPullRequests(
    GithubRepository $repo,
    string $baseBranch = '',
    string $headRef = '',
    string $state = 'open'
): GithubPullRequestList
```

**Parameters:**
- `$repo` - The repository
- `$baseBranch` - Filter by base branch (optional)
- `$headRef` - Filter by head reference (optional)
- `$state` - Filter by state: 'open', 'closed', 'all' (default: 'open')

**Returns:** Collection of pull requests

#### getPullRequest()

Get a single pull request with complete details.

```php
public function getPullRequest(
    GithubRepository $repo,
    int $number
): GithubPullRequest
```

**Returns:** Complete pull request details

#### createPullRequest()

Create a new pull request.

```php
public function createPullRequest(
    GithubRepository $repo,
    CreatePullRequestParams $params
): GithubPullRequest
```

**Parameters:**
- `$repo` - The repository
- `$params` - Pull request creation parameters

**Returns:** The created pull request

#### updatePullRequest()

Update a pull request's title, body, base branch, or state.

```php
public function updatePullRequest(
    GithubRepository $repo,
    int $number,
    PullRequestUpdate $update
): GithubPullRequest
```

**Returns:** Updated pull request

#### reopenPullRequest()

Reopen a closed pull request.

```php
public function reopenPullRequest(
    GithubRepository $repo,
    int $number
): GithubPullRequest
```

**Returns:** Reopened pull request

#### mergePullRequest()

Merge a pull request.

```php
public function mergePullRequest(
    GithubRepository $repo,
    int $number,
    MergePullRequestParams $params
): MergeResult
```

**Parameters:**
- `$params` - Merge configuration (method, commit message, etc.)

**Returns:** Merge operation result

#### closePullRequest()

Close a pull request without merging.

```php
public function closePullRequest(
    GithubRepository $repo,
    int $number
): GithubPullRequest
```

**Returns:** Closed pull request

### Comment Methods

#### listPullRequestComments()

List all comments on a pull request.

```php
public function listPullRequestComments(
    GithubRepository $repo,
    int $number
): GithubCommentList
```

**Returns:** Collection of comments

#### createPullRequestComment()

Create a comment on a pull request.

```php
public function createPullRequestComment(
    GithubRepository $repo,
    int $number,
    string $body
): GithubComment
```

**Returns:** Created comment

#### updateComment()

Update an existing comment.

```php
public function updateComment(
    GithubRepository $repo,
    int $commentId,
    string $body
): GithubComment
```

**Returns:** Updated comment

#### deleteComment()

Delete a comment.

```php
public function deleteComment(
    GithubRepository $repo,
    int $commentId
): void
```

### Review Methods

#### listPullRequestReviews()

List all reviews on a pull request.

```php
public function listPullRequestReviews(
    GithubRepository $repo,
    int $number
): GithubReviewList
```

**Returns:** Collection of reviews

#### requestReviewers()

Request reviewers for a pull request.

```php
public function requestReviewers(
    GithubRepository $repo,
    int $number,
    array $reviewers = [],
    array $teamReviewers = []
): GithubPullRequest
```

**Parameters:**
- `$reviewers` - Array of user logins
- `$teamReviewers` - Array of team slugs

**Returns:** Updated pull request with requested reviewers

### Status Check Methods

#### getCombinedStatus()

Get combined status for a commit.

```php
public function getCombinedStatus(
    GithubRepository $repo,
    string $ref
): GithubCombinedStatus
```

**Parameters:**
- `$ref` - Commit SHA, branch name, or tag name

**Returns:** Combined status with all checks

#### listCheckRuns()

List check runs (GitHub Actions) for a commit.

```php
public function listCheckRuns(
    GithubRepository $repo,
    string $ref
): GithubCheckRunList
```

**Parameters:**
- `$ref` - Commit SHA, branch name, or tag name

**Returns:** Collection of check runs

### Label Methods

#### listIssueLabels()

List labels on an issue or pull request.

```php
public function listIssueLabels(
    GithubRepository $repo,
    int $number
): GithubLabelList
```

**Returns:** Collection of labels

#### addLabels()

Add labels to an issue or pull request.

```php
public function addLabels(
    GithubRepository $repo,
    int $number,
    array $labels
): GithubLabelList
```

**Parameters:**
- `$labels` - Array of label names

**Returns:** Updated list of labels

#### setLabels()

Set (replace) all labels on an issue or pull request.

```php
public function setLabels(
    GithubRepository $repo,
    int $number,
    array $labels
): GithubLabelList
```

**Parameters:**
- `$labels` - Array of label names

**Returns:** Updated list of labels

#### removeLabel()

Remove a label from an issue or pull request.

```php
public function removeLabel(
    GithubRepository $repo,
    int $number,
    string $labelName
): void
```

### Rate Limit Methods

#### getRateLimit()

Get current rate limit status.

```php
public function getRateLimit(): RateLimit
```

**Returns:** Rate limit information

#### getTokenScopes()

Get OAuth scopes for the current access token.

```php
public function getTokenScopes(): TokenScopes
```

**Returns:** Token scope information

## Configuration

### GithubApiConfig

Configuration object containing API credentials.

```php
public function __construct(
    public readonly string $accessToken
)
```

## Value Objects

### GithubPullRequest

Represents a GitHub pull request.

**Properties:**
- `int $number` - Pull request number
- `string $title` - Pull request title
- `string $state` - State: 'open', 'closed', 'merged'
- `string $htmlUrl` - Web URL
- `?string $body` - Description body
- `bool $draft` - Is draft PR
- `bool $merged` - Is merged
- `?string $mergedAt` - Merge timestamp
- `string $createdAt` - Creation timestamp
- `string $updatedAt` - Last update timestamp
- `string $headRef` - Head branch name
- `string $baseRef` - Base branch name
- `string $headSha` - Head commit SHA
- `?GithubUser $author` - PR author
- `array<GithubLabel> $labels` - Labels
- `array<GithubUser> $requestedReviewers` - Requested reviewers
- `?bool $mergeable` - Can be merged
- `?string $mergeableState` - Mergeable state

### GithubComment

Represents a comment on a pull request.

**Properties:**
- `int $id` - Comment ID
- `string $body` - Comment text
- `GithubUser $author` - Comment author
- `string $createdAt` - Creation timestamp
- `string $updatedAt` - Last update timestamp
- `string $htmlUrl` - Web URL
- `string $apiUrl` - API URL

### GithubReview

Represents a pull request review.

**Properties:**
- `int $id` - Review ID
- `GithubUser $user` - Reviewer
- `string $body` - Review comment
- `string $state` - Review state: 'APPROVED', 'CHANGES_REQUESTED', 'COMMENTED', 'DISMISSED'
- `string $htmlUrl` - Web URL
- `string $submittedAt` - Submission timestamp
- `string $commitId` - Commit SHA reviewed

### GithubUser

Represents a GitHub user.

**Properties:**
- `string $login` - Username
- `int $id` - User ID
- `string $avatarUrl` - Avatar URL
- `string $htmlUrl` - Profile URL
- `string $type` - User type: 'User', 'Bot', 'Organization'

### GithubLabel

Represents a label.

**Properties:**
- `string $name` - Label name
- `string $color` - Hex color (without #)
- `string $description` - Label description

### GithubCommitStatus

Represents a commit status check.

**Properties:**
- `string $state` - Status: 'success', 'failure', 'pending', 'error'
- `string $context` - Check context/name
- `string $description` - Status description
- `string $targetUrl` - Details URL
- `string $createdAt` - Creation timestamp
- `string $updatedAt` - Update timestamp

### GithubCombinedStatus

Represents combined status of all checks.

**Properties:**
- `string $state` - Overall state
- `string $sha` - Commit SHA
- `int $totalCount` - Total number of checks
- `array<GithubCommitStatus> $statuses` - Individual statuses

### GithubCheckRun

Represents a GitHub Actions check run.

**Properties:**
- `int $id` - Check run ID
- `string $name` - Check name
- `string $status` - Status: 'queued', 'in_progress', 'completed'
- `string $conclusion` - Conclusion: 'success', 'failure', 'neutral', 'cancelled', 'skipped', 'timed_out', 'action_required'
- `string $headSha` - Commit SHA
- `string $htmlUrl` - Web URL
- `string $detailsUrl` - Details URL
- `string $startedAt` - Start timestamp
- `string $completedAt` - Completion timestamp

### MergeResult

Represents the result of a merge operation.

**Properties:**
- `string $sha` - Merge commit SHA
- `bool $merged` - Was merge successful
- `string $message` - Result message

## Collections

All collection classes implement `Iterator` and `Countable` interfaces.

- `GithubRepositoryList` - Collection of repositories
- `GithubPullRequestList` - Collection of pull requests
- `GithubCommentList` - Collection of comments
- `GithubReviewList` - Collection of reviews
- `GithubLabelList` - Collection of labels
- `GithubCheckRunList` - Collection of check runs

**Methods:**
- `count(): int` - Get number of items
- `toArray(): array` - Convert to array
- Plus all standard iterator methods

## DTOs

### CreatePullRequestParams

Data transfer object for creating pull requests.

```php
public function __construct(
    public readonly string $title,
    public readonly string $head,
    public readonly string $base,
    public readonly string $body = '',
    public readonly bool $draft = false,
    public readonly bool $maintainerCanModify = true
)
```

**Parameters:**
- `$title` - The title of the pull request (required)
- `$head` - The name of the branch where your changes are (required)
  - Can be a simple branch name: `'feature-branch'`
  - Can include owner prefix for forks: `'username:feature-branch'`
- `$base` - The name of the branch you want changes pulled into (required)
- `$body` - The description/body of the pull request (optional)
- `$draft` - Whether to create as a draft PR (optional, default: false)
- `$maintainerCanModify` - Whether maintainers can modify the PR (optional, default: true)

**Methods:**
- `toArray(): array` - Convert to API payload

### PullRequestUpdate

Data transfer object for updating pull requests.

```php
public function __construct(
    public readonly ?string $title = null,
    public readonly ?string $body = null,
    public readonly ?string $base = null,
    public readonly ?string $state = null
)
```

**Methods:**
- `toArray(): array` - Convert to API payload (excludes null fields)
- `isEmpty(): bool` - Check if any field is set

### MergePullRequestParams

Data transfer object for merge operations.

```php
public function __construct(
    public readonly string $commitTitle = '',
    public readonly string $commitMessage = '',
    public readonly string $mergeMethod = 'merge',
    public readonly string $sha = ''
)
```

**Parameters:**
- `$commitTitle` - Custom commit title
- `$commitMessage` - Custom commit message
- `$mergeMethod` - Merge method: 'merge', 'squash', 'rebase'
- `$sha` - Expected head SHA (for safe merging)

**Methods:**
- `toArray(): array` - Convert to API payload (excludes defaults)
