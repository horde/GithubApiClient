#!/usr/bin/env php
<?php

/**
 * A demo client for the github client library
 */
declare(strict_types=1);

namespace Horde\GithubApiClient;

// Development Reference cli
require_once dirname(__DIR__) . '/vendor/autoload.php';


use Horde\Injector\Injector;
use Horde\Injector\TopLevel;
use Horde\Http\Client\Options;
use Horde\Http\Client\Curl as CurlClient;
use Horde\Http\StreamFactory;
use Horde\Http\RequestFactory;
use Horde\Http\ResponseFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

// Bootstrap the injector
$strGithubApiToken = (string) getenv('GITHUB_TOKEN');
if (empty($strGithubApiToken)) {
    echo "\nNo can do. Did you forget to export GITHUB_TOKEN variable?\n";
    exit;
}
$injector = new Injector(new TopLevel());
// Setup a curl client. This is a demo, don't get too involved
$injector->setInstance(ClientInterface::class, new CurlClient(new ResponseFactory(), new StreamFactory(), new Options()));
$injector->setInstance(RequestFactoryInterface::class, new RequestFactory());
$injector->setInstance(StreamFactoryInterface::class, new StreamFactory());
$injector->setInstance(GithubApiConfig::class, new GithubApiConfig(accessToken: $strGithubApiToken));
$client = $injector->get(GithubApiClient::class);

echo "=== GitHub API Client Demo ===\n\n";

// Example 1: List repositories in organization
echo "1. Listing repositories in Horde organization:\n";
$repos = $client->listRepositoriesInOrganization(new GithubOrganizationId('horde'));
$count = 0;
foreach ($repos as $repo) {
    echo "  - {$repo->getFullName()}\n";
    if (++$count >= 5) {
        echo "  ... (showing first 5)\n";
        break;
    }
}
echo "\n";

// Example 2: Check rate limit
echo "2. Checking rate limit:\n";
$rateLimit = $client->getRateLimit();
echo "  Remaining: {$rateLimit->remaining}/{$rateLimit->limit}\n";
echo "  Resets at: {$rateLimit->reset}\n\n";

// Example 3: Check token scopes
echo "3. Checking token scopes:\n";
$scopes = $client->getTokenScopes();
echo "  Has repo scope: " . ($scopes->hasScope('repo') ? 'Yes' : 'No') . "\n";
echo "  Has workflow scope: " . ($scopes->hasScope('workflow') ? 'Yes' : 'No') . "\n\n";

// Example 4: List pull requests (if DEMO_REPO is set)
$demoRepo = getenv('DEMO_REPO');
if ($demoRepo && strpos($demoRepo, '/') !== false) {
    [$owner, $name] = explode('/', $demoRepo, 2);
    $repo = new GithubRepository(owner: $owner, name: $name);

    echo "4. Listing pull requests for {$demoRepo}:\n";
    $pullRequests = $client->listPullRequests($repo, state: 'open');
    $prCount = 0;
    foreach ($pullRequests as $pr) {
        echo "  PR #{$pr->number}: {$pr->title}\n";
        echo "    State: {$pr->state}, Draft: " . ($pr->draft ? 'Yes' : 'No') . "\n";

        if (++$prCount >= 3) {
            echo "  ... (showing first 3)\n";
            break;
        }
    }
    echo "\n";

    // Example 5: Get detailed PR info for first PR
    if ($prCount > 0) {
        $firstPr = $pullRequests->toArray()[0];
        echo "5. Getting detailed info for PR #{$firstPr->number}:\n";
        $detailedPr = $client->getPullRequest($repo, $firstPr->number);
        echo "  Author: {$detailedPr->author->login}\n";
        echo "  Created: {$detailedPr->createdAt}\n";
        echo "  Mergeable: " . ($detailedPr->mergeable ? 'Yes' : 'No') . "\n";
        echo "  Labels: " . count($detailedPr->labels) . "\n";
        echo "  Reviewers: " . count($detailedPr->requestedReviewers) . "\n\n";

        // Example 6: List comments
        echo "6. Listing comments on PR #{$firstPr->number}:\n";
        $comments = $client->listPullRequestComments($repo, $firstPr->number);
        echo "  Total comments: " . count($comments) . "\n";
        foreach ($comments as $comment) {
            echo "  - {$comment->author->login}: " . substr($comment->body, 0, 50) . "...\n";
            if (++$commentCount >= 3) {
                echo "  ... (showing first 3)\n";
                break;
            }
        }
        echo "\n";

        // Example 7: List reviews
        echo "7. Listing reviews on PR #{$firstPr->number}:\n";
        $reviews = $client->listPullRequestReviews($repo, $firstPr->number);
        echo "  Total reviews: " . count($reviews) . "\n";
        foreach ($reviews as $review) {
            echo "  - {$review->user->login}: {$review->state}\n";
        }
        echo "\n";

        // Example 8: Check CI/CD status
        echo "8. Checking CI/CD status for PR #{$firstPr->number}:\n";
        $status = $client->getCombinedStatus($repo, $detailedPr->headSha);
        echo "  Overall status: {$status->state}\n";
        echo "  Total checks: {$status->totalCount}\n";

        $checkRuns = $client->listCheckRuns($repo, $detailedPr->headSha);
        echo "  Check runs: " . count($checkRuns) . "\n";
        foreach ($checkRuns as $run) {
            echo "  - {$run->name}: {$run->status}";
            if ($run->conclusion) {
                echo " ({$run->conclusion})";
            }
            echo "\n";
        }
        echo "\n";
    }
} else {
    echo "4-8. Skipped (set DEMO_REPO=owner/repo to see PR examples)\n\n";
}

// Example 9: Create a pull request (if CREATE_PR_DEMO=1 is set)
if (getenv('CREATE_PR_DEMO') === '1' && $demoRepo && strpos($demoRepo, '/') !== false) {
    [$owner, $name] = explode('/', $demoRepo, 2);
    $repo = new GithubRepository(owner: $owner, name: $name);

    echo "9. Creating a demo pull request:\n";

    $headBranch = getenv('PR_HEAD_BRANCH') ?: 'demo-branch';
    $baseBranch = getenv('PR_BASE_BRANCH') ?: 'main';

    try {
        $createParams = new CreatePullRequestParams(
            title: 'Demo PR - API Client Test',
            head: $headBranch,
            base: $baseBranch,
            body: "This is a demo pull request created by the GitHub API Client.\n\n" .
                  "Created at: " . date('Y-m-d H:i:s') . "\n" .
                  "This PR can be safely closed.",
            draft: (getenv('PR_DRAFT') === '1'),
            maintainerCanModify: true
        );

        $newPr = $client->createPullRequest($repo, $createParams);
        echo "  ✓ Created PR #{$newPr->number}: {$newPr->title}\n";
        echo "  URL: {$newPr->htmlUrl}\n";
        echo "  State: {$newPr->state}\n";
        echo "  Draft: " . ($newPr->draft ? 'Yes' : 'No') . "\n";

        // Example 10: Demonstrate reopen functionality by closing and reopening
        if (getenv('DEMO_REOPEN') === '1') {
            echo "\n10. Demonstrating close and reopen:\n";

            // Close the PR
            $closedPr = $client->closePullRequest($repo, $newPr->number);
            echo "  ✓ Closed PR #{$closedPr->number}, state: {$closedPr->state}\n";

            sleep(1); // Brief pause for API rate limiting

            // Reopen the PR
            $reopenedPr = $client->reopenPullRequest($repo, $newPr->number);
            echo "  ✓ Reopened PR #{$reopenedPr->number}, state: {$reopenedPr->state}\n";
        }

    } catch (\Exception $e) {
        echo "  ✗ Error: {$e->getMessage()}\n";
        echo "  Note: Make sure the head branch exists and differs from base branch\n";
    }
    echo "\n";
}

echo "=== Demo Complete ===\n";
echo "\nTo see PR-related examples, export DEMO_REPO=owner/repo\n";
echo "Example: export DEMO_REPO=horde/components\n";
echo "\nTo test PR creation, also set:\n";
echo "  CREATE_PR_DEMO=1        Enable PR creation demo\n";
echo "  PR_HEAD_BRANCH=branch   Source branch (default: demo-branch)\n";
echo "  PR_BASE_BRANCH=branch   Target branch (default: main)\n";
echo "  PR_DRAFT=1              Create as draft PR\n";
echo "  DEMO_REOPEN=1           Demonstrate close/reopen functionality\n";
