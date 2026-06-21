<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub Issue Type (org-level taxonomy entry — Bug, Feature, Task, etc.)
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
class GithubIssueType implements Stringable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $color,
        public readonly bool $isEnabled,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly string $nodeId = '',
    ) {}

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Create from GitHub API response
     *
     * @param object $data Decoded JSON from API
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: $data->id ?? 0,
            name: $data->name ?? '',
            description: $data->description ?? null,
            color: $data->color ?? null,
            isEnabled: $data->is_enabled ?? true,
            createdAt: $data->created_at ?? '',
            updatedAt: $data->updated_at ?? '',
            nodeId: $data->node_id ?? '',
        );
    }
}
