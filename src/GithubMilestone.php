<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub repository milestone
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
class GithubMilestone implements Stringable
{
    public function __construct(
        public readonly int $id,
        public readonly int $number,
        public readonly string $title,
        public readonly string $description,
        public readonly string $state,
        public readonly ?GithubUser $creator,
        public readonly int $openIssues,
        public readonly int $closedIssues,
        public readonly string $htmlUrl,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $closedAt,
        public readonly ?string $dueOn,
        public readonly string $nodeId = '',
    ) {}

    public function __toString(): string
    {
        return $this->title;
    }

    /**
     * Create from GitHub API response
     *
     * @param object $data Decoded JSON from API
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $creator = null;
        if (isset($data->creator) && is_object($data->creator)) {
            $creator = GithubUser::fromApiResponse($data->creator);
        }

        return new self(
            id: $data->id ?? 0,
            number: $data->number ?? 0,
            title: $data->title ?? '',
            description: $data->description ?? '',
            state: $data->state ?? 'open',
            creator: $creator,
            openIssues: $data->open_issues ?? 0,
            closedIssues: $data->closed_issues ?? 0,
            htmlUrl: $data->html_url ?? '',
            createdAt: $data->created_at ?? '',
            updatedAt: $data->updated_at ?? '',
            closedAt: $data->closed_at ?? null,
            dueOn: $data->due_on ?? null,
            nodeId: $data->node_id ?? '',
        );
    }
}
