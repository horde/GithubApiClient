<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub commit status
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
class GithubCommitStatus implements Stringable
{
    public function __construct(
        public readonly string $state,
        public readonly string $context,
        public readonly string $description,
        public readonly string $targetUrl,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public function __toString(): string
    {
        return sprintf('%s: %s (%s)', $this->context, $this->state, $this->description);
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
            state: $data->state ?? '',
            context: $data->context ?? '',
            description: $data->description ?? '',
            targetUrl: $data->target_url ?? '',
            createdAt: $data->created_at ?? '',
            updatedAt: $data->updated_at ?? ''
        );
    }
}
