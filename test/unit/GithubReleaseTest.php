<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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
#[CoversClass(GithubRelease::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubReleaseTest extends TestCase
{
    public function testConstruction(): void
    {
        $author = new GithubUser(
            login: 'octocat',
            id: 1,
            avatarUrl: 'https://github.com/images/error/octocat_happy.gif',
            htmlUrl: 'https://github.com/octocat',
        );

        $asset = new GithubReleaseAsset(
            id: 1,
            name: 'example.zip',
            label: 'Example archive',
            contentType: 'application/zip',
            size: 1024,
            downloadCount: 42,
            state: 'uploaded',
            browserDownloadUrl: 'https://github.com/releases/download/v1.0.0/example.zip',
            createdAt: '2026-01-01T12:00:00Z',
            updatedAt: '2026-01-01T12:00:00Z',
        );

        $release = new GithubRelease(
            id: 123,
            tagName: 'v1.0.0',
            name: 'Version 1.0.0',
            body: 'Release notes',
            draft: false,
            prerelease: false,
            createdAt: '2026-01-01T10:00:00Z',
            publishedAt: '2026-01-01T11:00:00Z',
            htmlUrl: 'https://github.com/owner/repo/releases/tag/v1.0.0',
            uploadUrl: 'https://uploads.github.com/repos/owner/repo/releases/123/assets{?name,label}',
            author: $author,
            assets: [$asset],
        );

        $this->assertSame(123, $release->id);
        $this->assertSame('v1.0.0', $release->tagName);
        $this->assertSame('Version 1.0.0', $release->name);
        $this->assertSame('Release notes', $release->body);
        $this->assertFalse($release->draft);
        $this->assertFalse($release->prerelease);
        $this->assertSame('2026-01-01T10:00:00Z', $release->createdAt);
        $this->assertSame('2026-01-01T11:00:00Z', $release->publishedAt);
        $this->assertSame('https://github.com/owner/repo/releases/tag/v1.0.0', $release->htmlUrl);
        $this->assertSame('https://uploads.github.com/repos/owner/repo/releases/123/assets{?name,label}', $release->uploadUrl);
        $this->assertSame($author, $release->author);
        $this->assertCount(1, $release->assets);
        $this->assertSame($asset, $release->assets[0]);
    }

    public function testFromApiResponseComplete(): void
    {
        $data = json_decode(json_encode([
            'id' => 456,
            'tag_name' => 'v2.0.0',
            'name' => 'Version 2.0.0',
            'body' => 'Major release',
            'draft' => true,
            'prerelease' => false,
            'created_at' => '2026-02-01T12:00:00Z',
            'published_at' => '2026-02-01T13:00:00Z',
            'html_url' => 'https://github.com/owner/repo/releases/tag/v2.0.0',
            'upload_url' => 'https://uploads.github.com/repos/owner/repo/releases/456/assets{?name,label}',
            'author' => [
                'login' => 'developer',
                'id' => 99,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/99',
                'html_url' => 'https://github.com/developer',
            ],
            'assets' => [
                [
                    'id' => 789,
                    'name' => 'release.tar.gz',
                    'label' => 'Source code',
                    'content_type' => 'application/gzip',
                    'size' => 2048,
                    'download_count' => 100,
                    'state' => 'uploaded',
                    'browser_download_url' => 'https://github.com/releases/download/v2.0.0/release.tar.gz',
                    'created_at' => '2026-02-01T12:30:00Z',
                    'updated_at' => '2026-02-01T12:30:00Z',
                ],
            ],
        ]));

        $release = GithubRelease::fromApiResponse($data);

        $this->assertSame(456, $release->id);
        $this->assertSame('v2.0.0', $release->tagName);
        $this->assertSame('Version 2.0.0', $release->name);
        $this->assertSame('Major release', $release->body);
        $this->assertTrue($release->draft);
        $this->assertFalse($release->prerelease);
        $this->assertSame('2026-02-01T12:00:00Z', $release->createdAt);
        $this->assertSame('2026-02-01T13:00:00Z', $release->publishedAt);
        $this->assertSame('https://github.com/owner/repo/releases/tag/v2.0.0', $release->htmlUrl);
        $this->assertSame('https://uploads.github.com/repos/owner/repo/releases/456/assets{?name,label}', $release->uploadUrl);
        $this->assertSame('developer', $release->author->login);
        $this->assertCount(1, $release->assets);
        $this->assertSame('release.tar.gz', $release->assets[0]->name);
    }

    public function testFromApiResponseMinimal(): void
    {
        $data = json_decode(json_encode([
            'id' => 1,
            'tag_name' => 'v0.1.0',
            'name' => '',
            'body' => '',
            'draft' => false,
            'prerelease' => true,
            'created_at' => '2026-01-15T08:00:00Z',
            'published_at' => null,
            'html_url' => 'https://github.com/owner/repo/releases/tag/v0.1.0',
            'upload_url' => 'https://uploads.github.com/repos/owner/repo/releases/1/assets{?name,label}',
            'author' => [
                'login' => 'bot',
                'id' => 1,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/1',
                'html_url' => 'https://github.com/bot',
            ],
            'assets' => [],
        ]));

        $release = GithubRelease::fromApiResponse($data);

        $this->assertSame(1, $release->id);
        $this->assertSame('v0.1.0', $release->tagName);
        $this->assertSame('', $release->name);
        $this->assertSame('', $release->body);
        $this->assertFalse($release->draft);
        $this->assertTrue($release->prerelease);
        $this->assertSame('2026-01-15T08:00:00Z', $release->createdAt);
        $this->assertSame('', $release->publishedAt);
        $this->assertCount(0, $release->assets);
    }

    public function testFromApiResponseWithMultipleAssets(): void
    {
        $data = json_decode(json_encode([
            'id' => 999,
            'tag_name' => 'v3.0.0',
            'name' => 'Version 3.0.0',
            'body' => 'Release with multiple assets',
            'draft' => false,
            'prerelease' => false,
            'created_at' => '2026-03-01T00:00:00Z',
            'published_at' => '2026-03-01T01:00:00Z',
            'html_url' => 'https://github.com/owner/repo/releases/tag/v3.0.0',
            'upload_url' => 'https://uploads.github.com/repos/owner/repo/releases/999/assets{?name,label}',
            'author' => [
                'login' => 'maintainer',
                'id' => 123,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/123',
                'html_url' => 'https://github.com/maintainer',
            ],
            'assets' => [
                [
                    'id' => 1001,
                    'name' => 'app.exe',
                    'label' => 'Windows executable',
                    'content_type' => 'application/octet-stream',
                    'size' => 5120,
                    'download_count' => 250,
                    'state' => 'uploaded',
                    'browser_download_url' => 'https://github.com/releases/download/v3.0.0/app.exe',
                    'created_at' => '2026-03-01T00:30:00Z',
                    'updated_at' => '2026-03-01T00:30:00Z',
                ],
                [
                    'id' => 1002,
                    'name' => 'app.dmg',
                    'label' => 'macOS disk image',
                    'content_type' => 'application/x-apple-diskimage',
                    'size' => 10240,
                    'download_count' => 180,
                    'state' => 'uploaded',
                    'browser_download_url' => 'https://github.com/releases/download/v3.0.0/app.dmg',
                    'created_at' => '2026-03-01T00:35:00Z',
                    'updated_at' => '2026-03-01T00:35:00Z',
                ],
                [
                    'id' => 1003,
                    'name' => 'app.tar.gz',
                    'label' => 'Linux binary',
                    'content_type' => 'application/gzip',
                    'size' => 3072,
                    'download_count' => 320,
                    'state' => 'uploaded',
                    'browser_download_url' => 'https://github.com/releases/download/v3.0.0/app.tar.gz',
                    'created_at' => '2026-03-01T00:40:00Z',
                    'updated_at' => '2026-03-01T00:40:00Z',
                ],
            ],
        ]));

        $release = GithubRelease::fromApiResponse($data);

        $this->assertCount(3, $release->assets);
        $this->assertSame('app.exe', $release->assets[0]->name);
        $this->assertSame('app.dmg', $release->assets[1]->name);
        $this->assertSame('app.tar.gz', $release->assets[2]->name);
    }

    public function testFromApiResponseDraftRelease(): void
    {
        $data = json_decode(json_encode([
            'id' => 111,
            'tag_name' => 'v1.0.0-rc1',
            'name' => 'Release Candidate 1',
            'body' => 'Testing before final release',
            'draft' => true,
            'prerelease' => true,
            'created_at' => '2026-02-20T10:00:00Z',
            'published_at' => null,
            'html_url' => 'https://github.com/owner/repo/releases/tag/v1.0.0-rc1',
            'upload_url' => 'https://uploads.github.com/repos/owner/repo/releases/111/assets{?name,label}',
            'author' => [
                'login' => 'tester',
                'id' => 50,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/50',
                'html_url' => 'https://github.com/tester',
            ],
            'assets' => [],
        ]));

        $release = GithubRelease::fromApiResponse($data);

        $this->assertTrue($release->draft);
        $this->assertTrue($release->prerelease);
        $this->assertSame('', $release->publishedAt);
    }

    public function testFromApiResponseWithNullPublishedAt(): void
    {
        $data = json_decode(json_encode([
            'id' => 222,
            'tag_name' => 'v1.5.0',
            'name' => 'Unpublished',
            'body' => 'Not yet published',
            'draft' => true,
            'prerelease' => false,
            'created_at' => '2026-02-25T15:00:00Z',
            'published_at' => null,
            'html_url' => 'https://github.com/owner/repo/releases/tag/v1.5.0',
            'upload_url' => 'https://uploads.github.com/repos/owner/repo/releases/222/assets{?name,label}',
            'author' => [
                'login' => 'author',
                'id' => 10,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/10',
                'html_url' => 'https://github.com/author',
            ],
            'assets' => [],
        ]));

        $release = GithubRelease::fromApiResponse($data);

        $this->assertSame('', $release->publishedAt);
    }
}
