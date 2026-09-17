<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Factory for creating GithubIssueEvent objects from API responses
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
class GithubIssueEventFactory
{
    /**
     * Create GithubIssueEvent from GitHub API response
     *
     * @param object $data Decoded JSON event object from API
     * @return GithubIssueEvent
     */
    public function createFromApiResponse(object $data): GithubIssueEvent
    {
        return GithubIssueEvent::fromApiResponse($data);
    }
}
