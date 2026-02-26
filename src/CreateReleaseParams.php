<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for creating a GitHub release
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
class CreateReleaseParams
{
    /**
     * @param string $tagName The name of the tag (required)
     * @param string $name The name of the release (optional)
     * @param string $body Text describing the contents of the release (optional)
     * @param bool $draft True to create a draft (unpublished) release (default: false)
     * @param bool $prerelease True to identify the release as a prerelease (default: false)
     * @param string $targetCommitish Specifies the commitish value for tag creation (optional)
     */
    public function __construct(
        public readonly string $tagName,
        public readonly string $name = '',
        public readonly string $body = '',
        public readonly bool $draft = false,
        public readonly bool $prerelease = false,
        public readonly string $targetCommitish = '',
    ) {}

    /**
     * Convert to array for API request
     *
     * @return array<string, string|bool>
     */
    public function toArray(): array
    {
        $data = [
            'tag_name' => $this->tagName,
            'draft' => $this->draft,
            'prerelease' => $this->prerelease,
        ];

        if ($this->name !== '') {
            $data['name'] = $this->name;
        }

        if ($this->body !== '') {
            $data['body'] = $this->body;
        }

        if ($this->targetCommitish !== '') {
            $data['target_commitish'] = $this->targetCommitish;
        }

        return $data;
    }
}
