<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for creating a pull-request review comment
 * (per-line / per-diff-position annotation, distinct from the
 * conversation-thread comments that createPullRequestComment posts).
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
class CreateReviewCommentParams
{
    /**
     * @param string $body Comment body (required)
     * @param string $commitId Head SHA of the PR; emitted as commit_id (required)
     * @param string $path Repo-relative file path (required)
     * @param int $line Line number in the file to anchor on (required)
     * @param string $side LEFT or RIGHT (default: RIGHT — the new version of the file)
     * @param int|null $startLine First line of a multi-line range; emitted as start_line when set
     * @param string|null $startSide Side of the diff for the start of a multi-line range; emitted as start_side when set
     * @param string $subjectType 'line' (default) or 'file'; emitted as subject_type
     * @param int|null $inReplyTo Reply target comment id; emitted as in_reply_to when set
     */
    public function __construct(
        public readonly string $body,
        public readonly string $commitId,
        public readonly string $path,
        public readonly int $line,
        public readonly string $side = 'RIGHT',
        public readonly ?int $startLine = null,
        public readonly ?string $startSide = null,
        public readonly string $subjectType = 'line',
        public readonly ?int $inReplyTo = null,
    ) {}

    /**
     * Convert to array for API request body.
     *
     * Required fields always emit. Side defaults to RIGHT and subjectType to
     * 'line' — both emit at their default because they carry semantic intent;
     * the GitHub API accepts them either way and explicit is clearer.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'body' => $this->body,
            'commit_id' => $this->commitId,
            'path' => $this->path,
            'line' => $this->line,
            'side' => $this->side,
            'subject_type' => $this->subjectType,
        ];

        if ($this->startLine !== null) {
            $data['start_line'] = $this->startLine;
        }

        if ($this->startSide !== null) {
            $data['start_side'] = $this->startSide;
        }

        if ($this->inReplyTo !== null) {
            $data['in_reply_to'] = $this->inReplyTo;
        }

        return $data;
    }
}
