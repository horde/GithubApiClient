<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Factory for creating GithubReview instances
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
class GithubReviewFactory
{
    /**
     * Create a GithubReview from API response data
     *
     * @param object $data The API response data
     * @return GithubReview
     */
    public function createFromApiResponse(object $data): GithubReview
    {
        return GithubReview::fromApiResponse($data);
    }
}
