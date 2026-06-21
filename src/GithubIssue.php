<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub issue
 *
 * Note on the issue ↔ PR relationship: in a given repository, issues and pull
 * requests share a single integer counter. Every PR is also an issue from the
 * labels/comments/assignees/milestone perspective; the `isPullRequest` flag
 * surfaces that overlap so callers can detect it without a second request.
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class GithubIssue implements Stringable
{
    /**
     * @param GithubUser[] $assignees Users currently assigned (may be empty)
     * @param GithubLabel[] $labels Labels currently attached (may be empty)
     * @param array<string, mixed> $fieldValues Custom Issue Field values (from issue_field_values); empty array when none set
     */
    public function __construct(
        public readonly int $id,
        public readonly int $number,
        public readonly string $title,
        public readonly string $body,
        public readonly string $state,
        public readonly ?string $stateReason,
        public readonly string $htmlUrl,
        public readonly string $apiUrl,
        public readonly GithubUser $author,
        public readonly array $labels,
        public readonly array $assignees,
        public readonly ?GithubMilestone $milestone,
        public readonly ?GithubIssueType $type,
        public readonly int $comments,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $closedAt,
        public readonly bool $isPullRequest,
        public readonly array $fieldValues = [],
        public readonly string $nodeId = '',
    ) {}

    public function __toString(): string
    {
        return sprintf('#%d %s', $this->number, $this->title);
    }
}
