<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Factory for creating GithubUser objects from API responses
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
class GithubUserFactory
{
    /**
     * Create GithubUser from GitHub API response
     *
     * @param object $data Decoded JSON user object from API
     * @return GithubUser
     */
    public function createFromApiResponse(object $data): GithubUser
    {
        return GithubUser::fromApiResponse($data);
    }
}
