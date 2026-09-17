<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a single item in an issue's timeline, as returned by
 * `GET /repos/{owner}/{repo}/issues/{issue_number}/timeline`.
 *
 * Timeline items are polymorphic — a "commented" item carries a comment
 * body, a "cross-referenced" item carries a source issue/PR, a
 * "committed" item carries commit data, and so on. This class models
 * the fields common across most item types and exposes the full
 * decoded item via `raw` so callers can pick out type-specific fields.
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
class GithubTimelineEvent implements Stringable
{
    /**
     * @param array<string, mixed> $raw Full decoded timeline item
     */
    public function __construct(
        public readonly string $event,
        public readonly ?GithubUser $actor,
        public readonly string $createdAt,
        public readonly array $raw,
        public readonly int $id = 0,
        public readonly string $nodeId = '',
    ) {}

    public function __toString(): string
    {
        return $this->event;
    }

    /**
     * Create GithubTimelineEvent from GitHub API response
     *
     * @param object $data Decoded JSON timeline item from API
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $userFactory = new GithubUserFactory();

        return new self(
            event: $data->event ?? '',
            actor: isset($data->actor) ? $userFactory->createFromApiResponse($data->actor) : null,
            createdAt: $data->created_at ?? '',
            raw: (array) $data,
            id: $data->id ?? 0,
            nodeId: $data->node_id ?? '',
        );
    }
}
