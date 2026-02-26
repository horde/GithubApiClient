<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub check run
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
class GithubCheckRun implements Stringable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $status,
        public readonly string $conclusion,
        public readonly string $headSha,
        public readonly string $htmlUrl,
        public readonly string $detailsUrl,
        public readonly string $startedAt,
        public readonly string $completedAt
    ) {}

    public function __toString(): string
    {
        return sprintf('%s: %s/%s', $this->name, $this->status, $this->conclusion);
    }

    /**
     * Create from GitHub API response
     *
     * @param object $data The API response data
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: $data->id ?? 0,
            name: $data->name ?? '',
            status: $data->status ?? '',
            conclusion: $data->conclusion ?? '',
            headSha: $data->head_sha ?? '',
            htmlUrl: $data->html_url ?? '',
            detailsUrl: $data->details_url ?? '',
            startedAt: $data->started_at ?? '',
            completedAt: $data->completed_at ?? ''
        );
    }
}
