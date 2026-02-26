<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
#[CoversClass(GithubReleaseAsset::class)]
class GithubReleaseAssetTest extends TestCase
{
    public function testConstruction(): void
    {
        $asset = new GithubReleaseAsset(
            id: 123,
            name: 'example.zip',
            label: 'Example archive',
            contentType: 'application/zip',
            size: 2048,
            downloadCount: 42,
            state: 'uploaded',
            browserDownloadUrl: 'https://github.com/releases/download/v1.0.0/example.zip',
            createdAt: '2026-01-01T12:00:00Z',
            updatedAt: '2026-01-01T12:30:00Z',
        );

        $this->assertSame(123, $asset->id);
        $this->assertSame('example.zip', $asset->name);
        $this->assertSame('Example archive', $asset->label);
        $this->assertSame('application/zip', $asset->contentType);
        $this->assertSame(2048, $asset->size);
        $this->assertSame(42, $asset->downloadCount);
        $this->assertSame('uploaded', $asset->state);
        $this->assertSame('https://github.com/releases/download/v1.0.0/example.zip', $asset->browserDownloadUrl);
        $this->assertSame('2026-01-01T12:00:00Z', $asset->createdAt);
        $this->assertSame('2026-01-01T12:30:00Z', $asset->updatedAt);
    }

    public function testFromApiResponse(): void
    {
        $data = json_decode(json_encode([
            'id' => 456,
            'name' => 'release.tar.gz',
            'label' => 'Source code',
            'content_type' => 'application/gzip',
            'size' => 4096,
            'download_count' => 150,
            'state' => 'uploaded',
            'browser_download_url' => 'https://github.com/releases/download/v2.0.0/release.tar.gz',
            'created_at' => '2026-02-01T10:00:00Z',
            'updated_at' => '2026-02-01T10:05:00Z',
        ]));

        $asset = GithubReleaseAsset::fromApiResponse($data);

        $this->assertSame(456, $asset->id);
        $this->assertSame('release.tar.gz', $asset->name);
        $this->assertSame('Source code', $asset->label);
        $this->assertSame('application/gzip', $asset->contentType);
        $this->assertSame(4096, $asset->size);
        $this->assertSame(150, $asset->downloadCount);
        $this->assertSame('uploaded', $asset->state);
        $this->assertSame('https://github.com/releases/download/v2.0.0/release.tar.gz', $asset->browserDownloadUrl);
        $this->assertSame('2026-02-01T10:00:00Z', $asset->createdAt);
        $this->assertSame('2026-02-01T10:05:00Z', $asset->updatedAt);
    }

    public function testFromApiResponseWithEmptyLabel(): void
    {
        $data = json_decode(json_encode([
            'id' => 789,
            'name' => 'binary.exe',
            'label' => '',
            'content_type' => 'application/octet-stream',
            'size' => 8192,
            'download_count' => 0,
            'state' => 'uploaded',
            'browser_download_url' => 'https://github.com/releases/download/v1.0.0/binary.exe',
            'created_at' => '2026-01-15T14:00:00Z',
            'updated_at' => '2026-01-15T14:00:00Z',
        ]));

        $asset = GithubReleaseAsset::fromApiResponse($data);

        $this->assertSame('', $asset->label);
    }

    public function testFromApiResponseZeroDownloads(): void
    {
        $data = json_decode(json_encode([
            'id' => 111,
            'name' => 'new-asset.dmg',
            'label' => 'macOS installer',
            'content_type' => 'application/x-apple-diskimage',
            'size' => 16384,
            'download_count' => 0,
            'state' => 'uploaded',
            'browser_download_url' => 'https://github.com/releases/download/v3.0.0/new-asset.dmg',
            'created_at' => '2026-03-01T08:00:00Z',
            'updated_at' => '2026-03-01T08:00:00Z',
        ]));

        $asset = GithubReleaseAsset::fromApiResponse($data);

        $this->assertSame(0, $asset->downloadCount);
    }

    public function testFromApiResponseLargeFile(): void
    {
        $data = json_decode(json_encode([
            'id' => 222,
            'name' => 'large-file.iso',
            'label' => 'Installation media',
            'content_type' => 'application/x-iso9660-image',
            'size' => 4294967296, // 4GB
            'download_count' => 1000,
            'state' => 'uploaded',
            'browser_download_url' => 'https://github.com/releases/download/v5.0.0/large-file.iso',
            'created_at' => '2026-02-15T00:00:00Z',
            'updated_at' => '2026-02-15T01:00:00Z',
        ]));

        $asset = GithubReleaseAsset::fromApiResponse($data);

        $this->assertSame(4294967296, $asset->size);
    }

    public function testFromApiResponseDifferentContentTypes(): void
    {
        $testCases = [
            'application/zip',
            'application/gzip',
            'application/x-tar',
            'application/octet-stream',
            'application/x-apple-diskimage',
            'application/vnd.debian.binary-package',
            'application/x-rpm',
        ];

        foreach ($testCases as $index => $contentType) {
            $data = json_decode(json_encode([
                'id' => $index + 1,
                'name' => "file-{$index}.bin",
                'label' => "File {$index}",
                'content_type' => $contentType,
                'size' => 1024,
                'download_count' => 10,
                'state' => 'uploaded',
                'browser_download_url' => "https://github.com/releases/download/v1.0.0/file-{$index}.bin",
                'created_at' => '2026-01-01T00:00:00Z',
                'updated_at' => '2026-01-01T00:00:00Z',
            ]));

            $asset = GithubReleaseAsset::fromApiResponse($data);

            $this->assertSame($contentType, $asset->contentType);
        }
    }

    public function testFromApiResponseDifferentStates(): void
    {
        $states = ['uploaded', 'open', 'starter'];

        foreach ($states as $index => $state) {
            $data = json_decode(json_encode([
                'id' => $index + 100,
                'name' => "asset-{$state}.bin",
                'label' => "Asset in {$state} state",
                'content_type' => 'application/octet-stream',
                'size' => 512,
                'download_count' => 5,
                'state' => $state,
                'browser_download_url' => "https://github.com/releases/download/v1.0.0/asset-{$state}.bin",
                'created_at' => '2026-01-01T00:00:00Z',
                'updated_at' => '2026-01-01T00:00:00Z',
            ]));

            $asset = GithubReleaseAsset::fromApiResponse($data);

            $this->assertSame($state, $asset->state);
        }
    }

    public function testFromApiResponseHighDownloadCount(): void
    {
        $data = json_decode(json_encode([
            'id' => 999,
            'name' => 'popular-asset.zip',
            'label' => 'Very popular',
            'content_type' => 'application/zip',
            'size' => 1024,
            'download_count' => 1000000,
            'state' => 'uploaded',
            'browser_download_url' => 'https://github.com/releases/download/v1.0.0/popular-asset.zip',
            'created_at' => '2025-01-01T00:00:00Z',
            'updated_at' => '2025-01-01T00:00:00Z',
        ]));

        $asset = GithubReleaseAsset::fromApiResponse($data);

        $this->assertSame(1000000, $asset->downloadCount);
    }

    public function testFromApiResponseWithSpecialCharactersInName(): void
    {
        $data = json_decode(json_encode([
            'id' => 333,
            'name' => 'my-app-v1.0.0-alpha.1+build.123.tar.gz',
            'label' => 'Special version naming',
            'content_type' => 'application/gzip',
            'size' => 2048,
            'download_count' => 25,
            'state' => 'uploaded',
            'browser_download_url' => 'https://github.com/releases/download/v1.0.0-alpha.1/my-app-v1.0.0-alpha.1+build.123.tar.gz',
            'created_at' => '2026-02-10T12:00:00Z',
            'updated_at' => '2026-02-10T12:00:00Z',
        ]));

        $asset = GithubReleaseAsset::fromApiResponse($data);

        $this->assertSame('my-app-v1.0.0-alpha.1+build.123.tar.gz', $asset->name);
    }
}
