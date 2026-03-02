<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub App
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
class GithubApp implements Stringable
{
    /**
     * @param int $id App ID
     * @param string $slug App slug
     * @param string $name App name
     * @param GithubUser $owner App owner
     * @param string $createdAt Creation timestamp
     * @param string $updatedAt Last update timestamp
     */
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly GithubUser $owner,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public function __toString(): string
    {
        return $this->slug;
    }

    /**
     * Create from GitHub API response
     *
     * @param object $data The API response data
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $owner = isset($data->owner) ? GithubUser::fromApiResponse($data->owner) : new GithubUser('', 0, '', '', '');

        return new self(
            id: $data->id ?? 0,
            slug: $data->slug ?? '',
            name: $data->name ?? '',
            owner: $owner,
            createdAt: $data->created_at ?? '',
            updatedAt: $data->updated_at ?? ''
        );
    }
}
