<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a single repository activity event, as returned by
 * `GET /repos/{owner}/{repo}/events`.
 *
 * Unlike issue events, repository events use a lighter-weight actor
 * shape (no `html_url`) and carry a polymorphic `payload` that varies
 * by event `type` (e.g. `PushEvent`, `IssuesEvent`, `WatchEvent`).
 * The payload is exposed as a decoded array rather than a typed
 * object.
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
class GithubRepositoryEvent implements Stringable
{
    /**
     * @param array<string, mixed> $payload Decoded event payload
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly ?GithubUser $actor,
        public readonly string $repoName,
        public readonly array $payload,
        public readonly bool $public,
        public readonly string $createdAt,
    ) {}

    public function __toString(): string
    {
        return $this->type;
    }

    /**
     * Create GithubRepositoryEvent from GitHub API response
     *
     * @param object $data Decoded JSON event object from API
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $userFactory = new GithubUserFactory();

        return new self(
            id: $data->id ?? '',
            type: $data->type ?? '',
            actor: isset($data->actor) ? $userFactory->createFromApiResponse($data->actor) : null,
            repoName: $data->repo->name ?? '',
            payload: isset($data->payload) ? (array) $data->payload : [],
            public: $data->public ?? true,
            createdAt: $data->created_at ?? '',
        );
    }
}
