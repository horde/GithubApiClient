<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub pull-request review comment (per-line annotation)
 *
 * Distinct from GithubComment, which represents conversation-thread comments
 * on a PR. Review comments anchor to a specific file path and line number
 * in the diff.
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
class GithubReviewComment implements Stringable
{
    public function __construct(
        public readonly int $id,
        public readonly string $body,
        public readonly string $path,
        public readonly int $line,
        public readonly ?int $startLine,
        public readonly string $side,
        public readonly string $commitId,
        public readonly string $userLogin,
        public readonly string $htmlUrl,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public function __toString(): string
    {
        return sprintf('%s:%d by %s', $this->path, $this->line, $this->userLogin);
    }

    /**
     * Create from GitHub API response
     *
     * @param object $data Decoded JSON from API
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: $data->id ?? 0,
            body: $data->body ?? '',
            path: $data->path ?? '',
            line: $data->line ?? 0,
            startLine: $data->start_line ?? null,
            side: $data->side ?? '',
            commitId: $data->commit_id ?? '',
            userLogin: $data->user->login ?? '',
            htmlUrl: $data->html_url ?? '',
            createdAt: $data->created_at ?? '',
            updatedAt: $data->updated_at ?? '',
        );
    }
}
