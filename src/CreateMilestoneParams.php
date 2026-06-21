<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use DateTimeImmutable;

/**
 * Data transfer object for creating a milestone
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
class CreateMilestoneParams
{
    /**
     * @param string $title Milestone title (required)
     * @param string $state 'open' (default) or 'closed'
     * @param string $description Optional description; emitted only when non-empty
     * @param DateTimeImmutable|null $dueOn Optional due date; emitted as ISO 8601 in due_on
     */
    public function __construct(
        public readonly string $title,
        public readonly string $state = 'open',
        public readonly string $description = '',
        public readonly ?DateTimeImmutable $dueOn = null
    ) {}

    /**
     * Convert to array for API request body.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [
            'title' => $this->title,
            'state' => $this->state,
        ];

        if ($this->description !== '') {
            $data['description'] = $this->description;
        }
        if ($this->dueOn !== null) {
            $data['due_on'] = $this->dueOn->format(DATE_ATOM);
        }

        return $data;
    }
}
