<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for creating a GitHub pull request review
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
class CreateReviewParams
{
    /**
     * @param string $event The review action: APPROVE, REQUEST_CHANGES, or COMMENT (required)
     * @param string $body The body text of the pull request review (optional for APPROVE, required for REQUEST_CHANGES)
     * @param string $commitId The SHA of the commit that needs a review (optional, defaults to latest)
     */
    public function __construct(
        public readonly string $event,
        public readonly string $body = '',
        public readonly string $commitId = '',
    ) {
        if (!in_array($event, ['APPROVE', 'REQUEST_CHANGES', 'COMMENT'])) {
            throw new \InvalidArgumentException(
                "Invalid event: {$event}. Must be APPROVE, REQUEST_CHANGES, or COMMENT"
            );
        }

        if ($event === 'REQUEST_CHANGES' && $body === '') {
            throw new \InvalidArgumentException(
                'Body is required when event is REQUEST_CHANGES'
            );
        }
    }

    /**
     * Convert to array for API request
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [
            'event' => $this->event,
        ];

        if ($this->body !== '') {
            $data['body'] = $this->body;
        }

        if ($this->commitId !== '') {
            $data['commit_id'] = $this->commitId;
        }

        return $data;
    }
}
