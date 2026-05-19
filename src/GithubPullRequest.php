<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use stdClass;
use Stringable;
use InvalidArgumentException;

/**
 * Represents a GitHub Pull Request
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class GithubPullRequest implements Stringable
{
    /**
     * Constructor - typically not called directly, use fromApiResponse() instead
     *
     * @internal This constructor has 20 parameters and should not be called directly by users.
     *           Use GithubPullRequest::fromApiResponse() or retrieve PRs via GithubApiClient methods.
     *
     * @param int $number PR number
     * @param string $title PR title
     * @param string $body PR description/body
     * @param string $htmlUrl PR web URL
     * @param string $apiUrl PR API URL
     * @param string $state open, closed
     * @param bool $draft Is draft PR
     * @param bool $merged Has been merged
     * @param ?string $mergedAt Merge timestamp (ISO 8601)
     * @param string $createdAt Creation timestamp (ISO 8601)
     * @param string $updatedAt Last update timestamp (ISO 8601)
     * @param GithubRepository $baseRepo Base repository
     * @param GithubRepository $headRepo Head repository
     * @param string $baseBranch Base branch name
     * @param string $headBranch Head branch name
     * @param GithubUser $author PR author
     * @param array<GithubLabel> $labels Labels on PR
     * @param array<GithubUser> $requestedReviewers Requested reviewers
     * @param ?string $mergeableState Mergeable state (clean, dirty, unstable, blocked, unknown)
     * @param ?bool $mergeable Can be merged
     */
    public function __construct(
        public readonly int $number,
        public readonly string $title,
        public readonly string $body,
        public readonly string $htmlUrl,
        public readonly string $apiUrl,
        public readonly string $state,
        public readonly bool $draft,
        public readonly bool $merged,
        public readonly ?string $mergedAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly GithubRepository $baseRepo,
        public readonly GithubRepository $headRepo,
        public readonly string $baseBranch,
        public readonly string $headBranch,
        public readonly GithubUser $author,
        public readonly array $labels,
        public readonly array $requestedReviewers,
        public readonly ?string $mergeableState,
        public readonly ?bool $mergeable,
        public readonly string $nodeId = '',
    ) {}

    public function __toString(): string
    {
        return $this->htmlUrl;
    }

    /**
     * Create GithubPullRequest from GitHub API response
     *
     * This is the recommended way to create GithubPullRequest objects.
     *
     * @param object $data Decoded JSON from GitHub API
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $factory = new GithubPullRequestFactory();
        return $factory->createFromApiResponse($data);
    }
}
