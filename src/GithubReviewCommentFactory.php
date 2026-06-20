<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Factory for creating GithubReviewComment objects from API responses
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
class GithubReviewCommentFactory
{
    /**
     * Create GithubReviewComment from GitHub API response
     *
     * @param object $data Decoded JSON review-comment object from API
     * @return GithubReviewComment
     */
    public function createFromApiResponse(object $data): GithubReviewComment
    {
        return GithubReviewComment::fromApiResponse($data);
    }
}
