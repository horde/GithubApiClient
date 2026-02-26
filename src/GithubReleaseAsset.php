<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub Release Asset (file attached to release)
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
class GithubReleaseAsset implements Stringable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $label,
        public readonly string $contentType,
        public readonly int $size,
        public readonly int $downloadCount,
        public readonly string $state,
        public readonly string $browserDownloadUrl,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public function __toString(): string
    {
        return $this->browserDownloadUrl;
    }

    /**
     * Create from GitHub API response
     *
     * @param object $data The API response data
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: $data->id ?? 0,
            name: $data->name ?? '',
            label: $data->label ?? '',
            contentType: $data->content_type ?? '',
            size: $data->size ?? 0,
            downloadCount: $data->download_count ?? 0,
            state: $data->state ?? '',
            browserDownloadUrl: $data->browser_download_url ?? '',
            createdAt: $data->created_at ?? '',
            updatedAt: $data->updated_at ?? '',
        );
    }
}
