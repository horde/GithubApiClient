<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for creating a pull request
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
class CreatePullRequestParams
{
    /**
     * @param string $title The title of the pull request (required)
     * @param string $head The name of the branch where your changes are implemented (required)
     * @param string $base The name of the branch you want the changes pulled into (required)
     * @param string $body The contents of the pull request (optional)
     * @param bool $draft Whether to create the pull request as a draft (optional, default: false)
     * @param bool $maintainerCanModify Whether maintainers can modify the pull request (optional, default: true)
     */
    public function __construct(
        public readonly string $title,
        public readonly string $head,
        public readonly string $base,
        public readonly string $body = '',
        public readonly bool $draft = false,
        public readonly bool $maintainerCanModify = true
    ) {}

    /**
     * Convert to array for API request
     *
     * @return array<string, string|bool>
     */
    public function toArray(): array
    {
        $data = [
            'title' => $this->title,
            'head' => $this->head,
            'base' => $this->base,
            'maintainer_can_modify' => $this->maintainerCanModify,
        ];

        if ($this->body !== '') {
            $data['body'] = $this->body;
        }

        if ($this->draft) {
            $data['draft'] = true;
        }

        return $data;
    }
}
