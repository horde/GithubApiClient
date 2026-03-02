<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub App installation
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
class GithubInstallation implements Stringable
{
    /**
     * @param int $id Installation ID
     * @param GithubUser $account Account (user or organization) that installed the app
     * @param string $repositorySelection Repository selection type (all/selected)
     * @param string $createdAt Creation timestamp
     * @param string $updatedAt Last update timestamp
     */
    public function __construct(
        public readonly int $id,
        public readonly GithubUser $account,
        public readonly string $repositorySelection,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public function __toString(): string
    {
        return (string) $this->id;
    }

    /**
     * Create from GitHub API response
     *
     * @param object $data The API response data
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $account = isset($data->account) ? GithubUser::fromApiResponse($data->account) : new GithubUser('', 0, '', '', '');

        return new self(
            id: $data->id ?? 0,
            account: $account,
            repositorySelection: $data->repository_selection ?? '',
            createdAt: $data->created_at ?? '',
            updatedAt: $data->updated_at ?? ''
        );
    }
}
