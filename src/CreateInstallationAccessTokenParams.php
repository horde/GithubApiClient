<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for creating an installation access token
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
class CreateInstallationAccessTokenParams
{
    /**
     * @param array<string> $repositories List of repository names to grant access to (optional)
     * @param array<string, string> $permissions Permissions to grant (optional)
     */
    public function __construct(
        public readonly array $repositories = [],
        public readonly array $permissions = []
    ) {}

    /**
     * Convert to array for API request
     *
     * @return array<string, array>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->repositories !== []) {
            $data['repositories'] = $this->repositories;
        }

        if ($this->permissions !== []) {
            $data['permissions'] = $this->permissions;
        }

        return $data;
    }
}
