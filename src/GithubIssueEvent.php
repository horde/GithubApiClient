<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a single event in an issue's event timeline (labeled,
 * assigned, closed, etc.), as returned by
 * `GET /repos/{owner}/{repo}/issues/{issue_number}/events`.
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
class GithubIssueEvent implements Stringable
{
    public function __construct(
        public readonly int $id,
        public readonly string $event,
        public readonly ?GithubUser $actor,
        public readonly string $createdAt,
        public readonly string $apiUrl,
        public readonly string $commitId = '',
        public readonly string $commitUrl = '',
        public readonly string $nodeId = '',
    ) {}

    public function __toString(): string
    {
        return $this->event;
    }

    /**
     * Create GithubIssueEvent from GitHub API response
     *
     * @param object $data Decoded JSON event object from API
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $userFactory = new GithubUserFactory();

        return new self(
            id: $data->id ?? 0,
            event: $data->event ?? '',
            actor: isset($data->actor) ? $userFactory->createFromApiResponse($data->actor) : null,
            createdAt: $data->created_at ?? '',
            apiUrl: $data->url ?? '',
            commitId: $data->commit_id ?? '',
            commitUrl: $data->commit_url ?? '',
            nodeId: $data->node_id ?? '',
        );
    }
}
