<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Represents a GitHub App installation access token
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
class InstallationAccessToken
{
    /**
     * @param string $token The installation access token
     * @param string $expiresAt Token expiration timestamp
     * @param array<string, string> $permissions Granted permissions
     * @param string $repositorySelection Repository selection type (all/selected)
     */
    public function __construct(
        public readonly string $token,
        public readonly string $expiresAt,
        public readonly array $permissions,
        public readonly string $repositorySelection
    ) {}

    /**
     * Create from GitHub API response
     *
     * @param object $data The API response data
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            token: $data->token ?? '',
            expiresAt: $data->expires_at ?? '',
            permissions: (array) ($data->permissions ?? []),
            repositorySelection: $data->repository_selection ?? ''
        );
    }
}
