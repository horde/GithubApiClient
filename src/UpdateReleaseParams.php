<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for updating a GitHub release
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
class UpdateReleaseParams
{
    public function __construct(
        public readonly ?string $tagName = null,
        public readonly ?string $name = null,
        public readonly ?string $body = null,
        public readonly ?bool $draft = null,
        public readonly ?bool $prerelease = null,
    ) {}

    /**
     * Convert to array for API request (only includes set fields)
     *
     * @return array<string, string|bool>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->tagName !== null) {
            $data['tag_name'] = $this->tagName;
        }

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->body !== null) {
            $data['body'] = $this->body;
        }

        if ($this->draft !== null) {
            $data['draft'] = $this->draft;
        }

        if ($this->prerelease !== null) {
            $data['prerelease'] = $this->prerelease;
        }

        return $data;
    }

    /**
     * Check if any field is set
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->tagName === null
            && $this->name === null
            && $this->body === null
            && $this->draft === null
            && $this->prerelease === null;
    }
}
