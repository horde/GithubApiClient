# horde/githubapiclient

A horde/http based client for the GitHub REST API v3.

## Installation

```bash
composer require horde/githubapiclient
```

**Upgrading?** See the [Migration Guide](doc/MIGRATION.md) for upgrading from earlier versions.

## Quick Start

```php
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubRepository;
use Horde\Http\Client;

// Create configuration
$config = new GithubApiConfig(accessToken: 'your-github-token');

// Create HTTP client (PSR-18 compatible)
$httpClient = new Client();
$requestFactory = new \Horde\Http\RequestFactory();
$streamFactory = new \Horde\Http\StreamFactory();

// Create API client
$client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);

// Use the client
$repo = new GithubRepository(owner: 'horde', name: 'components');
$pullRequests = $client->listPullRequests($repo);
```

## Features

### Repository Management
- List repositories in an organization
- Get repository details

### Pull Requests
- Create new pull requests
- List pull requests with filters (base branch, head ref, state)
- Get a single pull request with complete details
- Update pull request (title, body, base branch, state)
- Merge pull requests (merge, squash, or rebase)
- Close pull requests
- Reopen closed pull requests

### Comments
- List all comments on a pull request
- Create a comment on a pull request
- Update existing comments
- Delete comments

### Reviews
- List all reviews on a pull request
- Request reviewers (users and teams)
- Check review states (APPROVED, CHANGES_REQUESTED, COMMENTED, DISMISSED)

### Status Checks
- Get combined commit status
- List GitHub Actions check runs
- Monitor CI/CD pipeline status

### Labels
- List labels on issues/pull requests
- Add labels to issues/pull requests
- Set (replace) all labels on issues/pull requests
- Remove labels from issues/pull requests

### Rate Limiting
- Check current rate limit status
- Inspect OAuth token scopes

## Detailed Usage

### Working with Pull Requests

#### List Pull Requests

```php
$repo = new GithubRepository(owner: 'horde', name: 'components');

// List all open pull requests
$pullRequests = $client->listPullRequests($repo);

// Filter by base branch
$pullRequests = $client->listPullRequests($repo, baseBranch: 'main');

// Filter by head ref
$pullRequests = $client->listPullRequests($repo, headRef: 'feature-branch');

foreach ($pullRequests as $pr) {
    echo "PR #{$pr->number}: {$pr->title}\n";
}
```

#### Get Pull Request Details

```php
$pr = $client->getPullRequest($repo, number: 123);

echo "Title: {$pr->title}\n";
echo "State: {$pr->state}\n";
echo "Mergeable: " . ($pr->mergeable ? 'Yes' : 'No') . "\n";
echo "Draft: " . ($pr->draft ? 'Yes' : 'No') . "\n";
```

#### Create Pull Request

```php
use Horde\GithubApiClient\CreatePullRequestParams;

// Create a regular pull request
$params = new CreatePullRequestParams(
    title: 'Add new feature',
    head: 'feature-branch',
    base: 'main',
    body: 'This PR adds a new feature\n\nCloses #123'
);
$newPr = $client->createPullRequest($repo, $params);

// Create a draft pull request
$draftParams = new CreatePullRequestParams(
    title: 'Work in progress',
    head: 'wip-branch',
    base: 'develop',
    body: 'This is still being worked on',
    draft: true
);
$draftPr = $client->createPullRequest($repo, $draftParams);

// Create PR from a fork
$forkParams = new CreatePullRequestParams(
    title: 'Fix from fork',
    head: 'username:feature-branch',  // Format: username:branch
    base: 'main'
);
$forkPr = $client->createPullRequest($repo, $forkParams);
```

#### Update Pull Request

```php
use Horde\GithubApiClient\PullRequestUpdate;

// Update title and body
$update = new PullRequestUpdate(
    title: 'New PR Title',
    body: 'Updated description'
);
$updatedPr = $client->updatePullRequest($repo, 123, $update);

// Change base branch
$update = new PullRequestUpdate(base: 'develop');
$updatedPr = $client->updatePullRequest($repo, 123, $update);

// Close a pull request
$closedPr = $client->closePullRequest($repo, 123);

// Reopen a closed pull request
$reopenedPr = $client->reopenPullRequest($repo, 123);
```

#### Merge Pull Request

```php
use Horde\GithubApiClient\MergePullRequestParams;

// Simple merge with defaults
$params = new MergePullRequestParams();
$result = $client->mergePullRequest($repo, 123, $params);

if ($result->merged) {
    echo "Merged successfully: {$result->sha}\n";
}

// Squash merge with custom commit message
$params = new MergePullRequestParams(
    commitTitle: 'feat: add new feature',
    commitMessage: 'This PR implements feature X\n\nCloses #123',
    mergeMethod: 'squash'
);
$result = $client->mergePullRequest($repo, 123, $params);

// Rebase merge
$params = new MergePullRequestParams(mergeMethod: 'rebase');
$result = $client->mergePullRequest($repo, 123, $params);

// Safe merge with SHA check
$params = new MergePullRequestParams(
    sha: 'abc123def456',  // Only merge if head SHA matches
    mergeMethod: 'merge'
);
$result = $client->mergePullRequest($repo, 123, $params);
```

### Working with Comments

```php
// List comments
$comments = $client->listPullRequestComments($repo, 123);
foreach ($comments as $comment) {
    echo "{$comment->author->login}: {$comment->body}\n";
}

// Create a comment
$comment = $client->createPullRequestComment($repo, 123, 'Looks good to me!');
echo "Created comment: {$comment->htmlUrl}\n";

// Update a comment
$updatedComment = $client->updateComment($repo, $comment->id, 'Updated comment text');

// Delete a comment
$client->deleteComment($repo, $comment->id);
```

### Working with Reviews

```php
// List reviews
$reviews = $client->listPullRequestReviews($repo, 123);
foreach ($reviews as $review) {
    echo "{$review->user->login}: {$review->state}\n";
}

// Request reviewers
$updatedPr = $client->requestReviewers(
    $repo,
    123,
    reviewers: ['username1', 'username2'],
    teamReviewers: ['team-slug']
);
```

### Working with Status Checks

```php
// Get combined status for a commit or branch
$status = $client->getCombinedStatus($repo, 'main');
echo "Overall status: {$status->state}\n";
echo "Total checks: {$status->totalCount}\n";

foreach ($status->statuses as $check) {
    echo "{$check->context}: {$check->state}\n";
}

// List check runs (GitHub Actions)
$checkRuns = $client->listCheckRuns($repo, 'feature-branch');
foreach ($checkRuns as $run) {
    echo "{$run->name}: {$run->status}/{$run->conclusion}\n";
}
```

### Working with Labels

```php
// List labels
$labels = $client->listIssueLabels($repo, 123);
foreach ($labels as $label) {
    echo "{$label->name} (#{$label->color})\n";
}

// Add labels
$labels = $client->addLabels($repo, 123, ['bug', 'priority-high']);

// Set labels (replaces all existing labels)
$labels = $client->setLabels($repo, 123, ['bug', 'in-progress']);

// Remove a label
$client->removeLabel($repo, 123, 'wontfix');
```

### Rate Limiting and Token Information

```php
// Check rate limit
$rateLimit = $client->getRateLimit();
echo "Remaining: {$rateLimit->remaining}/{$rateLimit->limit}\n";
echo "Resets at: {$rateLimit->reset}\n";

// Check token scopes
$scopes = $client->getTokenScopes();
if ($scopes->hasScope('repo')) {
    echo "Token has repo access\n";
}
```

## Architecture

This library follows a Request Factory pattern:

- **Value Objects**: Immutable data classes (`GithubPullRequest`, `GithubComment`, etc.)
- **Factories**: Create value objects from API responses
- **Request Factories**: Build PSR-7 HTTP requests for each API endpoint
- **Collections**: Typed collections implementing `Iterator` and `Countable`
- **DTOs**: Data Transfer Objects for complex request parameters

All classes use PHP 8.2+ features including:
- Named parameters
- Readonly properties
- Strict types
- Constructor property promotion

## Testing

```bash
# Run unit tests
vendor/bin/phpunit

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

## Requirements

- PHP 8.2 or higher
- PSR-18 HTTP Client implementation
- PSR-7 HTTP Message implementation
- PSR-17 HTTP Factories implementation

## License

See the enclosed file LICENSE for license information (LGPL 2.1).

## Contributing

This package follows PER-1 coding standards with strict type declarations.

All commits should follow the Conventional Commits specification:
- `feat:` - New features
- `fix:` - Bug fixes
- `docs:` - Documentation changes
- `test:` - Test additions or modifications
- `refactor:` - Code refactoring
- `chore:` - Maintenance tasks

## Additional Documentation

- [API Reference](doc/API.md) - Complete API documentation for all classes and methods
- [Migration Guide](doc/MIGRATION.md) - Guide for upgrading from earlier versions
- `bin/demo-client.php` - Working examples of all features