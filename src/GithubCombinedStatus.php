<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents the combined status of all checks for a commit
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
class GithubCombinedStatus implements Stringable
{
    /**
     * @param array<GithubCommitStatus> $statuses
     */
    public function __construct(
        public readonly string $state,
        public readonly string $sha,
        public readonly int $totalCount,
        public readonly array $statuses
    ) {}

    public function __toString(): string
    {
        return sprintf('%s (%d checks)', $this->state, $this->totalCount);
    }

    /**
     * Create from GitHub API response
     *
     * @param object $data The API response data
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $statuses = [];
        if (isset($data->statuses) && is_array($data->statuses)) {
            foreach ($data->statuses as $statusData) {
                $statuses[] = GithubCommitStatus::fromApiResponse($statusData);
            }
        }

        return new self(
            state: $data->state ?? '',
            sha: $data->sha ?? '',
            totalCount: $data->total_count ?? 0,
            statuses: $statuses
        );
    }
}
