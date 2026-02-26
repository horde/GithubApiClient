<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data Transfer Object for pull request updates
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
class PullRequestUpdate
{
    /**
     * @param ?string $title New PR title
     * @param ?string $body New PR body/description
     * @param ?string $base New base branch
     * @param ?string $state New state (open or closed)
     */
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $body = null,
        public readonly ?string $base = null,
        public readonly ?string $state = null
    ) {}

    /**
     * Convert to array for JSON encoding (only includes set fields)
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->body !== null) {
            $data['body'] = $this->body;
        }

        if ($this->base !== null) {
            $data['base'] = $this->base;
        }

        if ($this->state !== null) {
            $data['state'] = $this->state;
        }

        return $data;
    }

    /**
     * Check if any field is set
     *
     * @return bool True if at least one field is set
     */
    public function isEmpty(): bool
    {
        return $this->title === null
            && $this->body === null
            && $this->base === null
            && $this->state === null;
    }
}
