<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub Release
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
class GithubRelease implements Stringable
{
    /**
     * @param int $id Release ID
     * @param string $tagName The name of the tag
     * @param string $name Release name/title
     * @param string $body Release notes (markdown)
     * @param bool $draft Is draft release
     * @param bool $prerelease Is prerelease
     * @param string $createdAt Creation timestamp
     * @param string $publishedAt Publication timestamp
     * @param string $htmlUrl Web URL
     * @param string $uploadUrl URL for uploading assets
     * @param GithubUser $author Release author
     * @param array<GithubReleaseAsset> $assets Release assets
     */
    public function __construct(
        public readonly int $id,
        public readonly string $tagName,
        public readonly string $name,
        public readonly string $body,
        public readonly bool $draft,
        public readonly bool $prerelease,
        public readonly string $createdAt,
        public readonly string $publishedAt,
        public readonly string $htmlUrl,
        public readonly string $uploadUrl,
        public readonly GithubUser $author,
        public readonly array $assets,
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
        $author = isset($data->author) ? GithubUser::fromApiResponse($data->author) : new GithubUser('', 0, '', '', '');

        $assets = [];
        if (isset($data->assets) && is_array($data->assets)) {
            foreach ($data->assets as $assetData) {
                $assets[] = GithubReleaseAsset::fromApiResponse($assetData);
            }
        }

        return new self(
            id: $data->id ?? 0,
            tagName: $data->tag_name ?? '',
            name: $data->name ?? '',
            body: $data->body ?? '',
            draft: $data->draft ?? false,
            prerelease: $data->prerelease ?? false,
            createdAt: $data->created_at ?? '',
            publishedAt: $data->published_at ?? '',
            htmlUrl: $data->html_url ?? '',
            uploadUrl: $data->upload_url ?? '',
            author: $author,
            assets: $assets,
            nodeId: $data->node_id ?? '',
        );
    }
}
