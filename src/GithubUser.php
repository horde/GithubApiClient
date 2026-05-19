<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub user (author, reviewer, commenter, etc.)
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
class GithubUser implements Stringable
{
    public function __construct(
        public readonly string $login,
        public readonly int $id,
        public readonly string $avatarUrl,
        public readonly string $htmlUrl,
        public readonly string $type = 'User',
        public readonly string $nodeId = '',
    ) {}

    public function __toString(): string
    {
        return $this->login;
    }

    /**
     * Create GithubUser from GitHub API response
     *
     * @param object $data Decoded JSON from API
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            login: $data->login ?? '',
            id: $data->id ?? 0,
            avatarUrl: $data->avatar_url ?? '',
            htmlUrl: $data->html_url ?? '',
            type: $data->type ?? 'User',
            nodeId: $data->node_id ?? '',
        );
    }
}
