<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for creating an issue
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
class CreateIssueParams
{
    /**
     * @param string $title Issue title (required)
     * @param string $body Issue body; emitted only when non-empty
     * @param string[] $assignees Usernames to assign; emitted only when non-empty
     * @param string[] $labels Label names; emitted only when non-empty
     * @param int|null $milestone Milestone number to assign; emitted only when set
     * @param string $type Issue type name; emitted only when non-empty
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body = '',
        public readonly array $assignees = [],
        public readonly array $labels = [],
        public readonly ?int $milestone = null,
        public readonly string $type = ''
    ) {}

    /**
     * Convert to array for API request body.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['title' => $this->title];

        if ($this->body !== '') {
            $data['body'] = $this->body;
        }
        if ($this->assignees !== []) {
            $data['assignees'] = $this->assignees;
        }
        if ($this->labels !== []) {
            $data['labels'] = $this->labels;
        }
        if ($this->milestone !== null) {
            $data['milestone'] = $this->milestone;
        }
        if ($this->type !== '') {
            $data['type'] = $this->type;
        }

        return $data;
    }
}
