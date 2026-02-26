<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Factory for creating GithubLabel objects from API responses
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
class GithubLabelFactory
{
    /**
     * Create GithubLabel from GitHub API response
     *
     * @param object $data Decoded JSON label object from API
     * @return GithubLabel
     */
    public function createFromApiResponse(object $data): GithubLabel
    {
        return GithubLabel::fromApiResponse($data);
    }
}
