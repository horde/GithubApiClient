<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub pull request review
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
class GithubReview implements Stringable
{
    public function __construct(
        public readonly int $id,
        public readonly GithubUser $user,
        public readonly string $body,
        public readonly string $state,
        public readonly string $htmlUrl,
        public readonly string $submittedAt,
        public readonly string $commitId,
        public readonly string $nodeId = '',
    ) {}

    public function __toString(): string
    {
        return $this->htmlUrl;
    }

    /**
     * Create from GitHub API response
     *
     * @param object $data The API response data
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $user = isset($data->user) ? GithubUser::fromApiResponse($data->user) : new GithubUser('', 0, '', '', '');

        return new self(
            id: $data->id ?? 0,
            user: $user,
            body: $data->body ?? '',
            state: $data->state ?? '',
            htmlUrl: $data->html_url ?? '',
            submittedAt: $data->submitted_at ?? '',
            commitId: $data->commit_id ?? '',
            nodeId: $data->node_id ?? '',
        );
    }
}
