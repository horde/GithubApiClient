<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Factory for creating GithubComment objects from API responses
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
class GithubCommentFactory
{
    /**
     * Create GithubComment from GitHub API response
     *
     * @param object $data Decoded JSON comment object from API
     * @return GithubComment
     */
    public function createFromApiResponse(object $data): GithubComment
    {
        return GithubComment::fromApiResponse($data);
    }
}
