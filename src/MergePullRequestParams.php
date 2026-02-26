<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for merge pull request parameters
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
class MergePullRequestParams
{
    /**
     * @param string $commitTitle The title for the merge commit (optional)
     * @param string $commitMessage The message body for the merge commit (optional)
     * @param string $mergeMethod The merge method: 'merge', 'squash', or 'rebase' (optional, default: 'merge')
     * @param string $sha The SHA that pull request head must match to allow merge (optional)
     */
    public function __construct(
        public readonly string $commitTitle = '',
        public readonly string $commitMessage = '',
        public readonly string $mergeMethod = 'merge',
        public readonly string $sha = ''
    ) {}

    /**
     * Convert to array for API request, excluding empty fields
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->commitTitle !== '') {
            $data['commit_title'] = $this->commitTitle;
        }
        if ($this->commitMessage !== '') {
            $data['commit_message'] = $this->commitMessage;
        }
        if ($this->mergeMethod !== 'merge') {
            $data['merge_method'] = $this->mergeMethod;
        }
        if ($this->sha !== '') {
            $data['sha'] = $this->sha;
        }

        return $data;
    }
}
