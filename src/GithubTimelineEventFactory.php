<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Factory for creating GithubTimelineEvent objects from API responses
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
class GithubTimelineEventFactory
{
    /**
     * Create GithubTimelineEvent from GitHub API response
     *
     * @param object $data Decoded JSON timeline item from API
     * @return GithubTimelineEvent
     */
    public function createFromApiResponse(object $data): GithubTimelineEvent
    {
        return GithubTimelineEvent::fromApiResponse($data);
    }
}
