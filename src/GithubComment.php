<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub pull request comment
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
class GithubComment implements Stringable
{
    public function __construct(
        public readonly int $id,
        public readonly string $body,
        public readonly GithubUser $author,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly string $htmlUrl,
        public readonly string $apiUrl
    ) {}

    public function __toString(): string
    {
        return $this->htmlUrl;
    }

    /**
     * Create GithubComment from GitHub API response
     *
     * @param object $data Decoded JSON from API
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        $userFactory = new GithubUserFactory();

        return new self(
            id: $data->id ?? 0,
            body: $data->body ?? '',
            author: $userFactory->createFromApiResponse($data->user),
            createdAt: $data->created_at ?? '',
            updatedAt: $data->updated_at ?? '',
            htmlUrl: $data->html_url ?? '',
            apiUrl: $data->url ?? ''
        );
    }
}
