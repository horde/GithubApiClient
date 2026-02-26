<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\CreateReleaseParams;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubRelease;
use Horde\GithubApiClient\GithubReleaseAsset;
use Horde\GithubApiClient\GithubRepository;
use Horde\GithubApiClient\UpdateReleaseParams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

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
#[CoversClass(GithubApiClient::class)]
class GithubApiClientReleaseTest extends TestCase
{
    public function testCreateReleaseSuccess(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $responseBodyContent = json_encode([
            'id' => 123,
            'tag_name' => 'v1.0.0',
            'name' => 'Version 1.0.0',
            'body' => 'Release notes',
            'draft' => false,
            'prerelease' => false,
            'created_at' => '2026-01-01T10:00:00Z',
            'published_at' => '2026-01-01T11:00:00Z',
            'html_url' => 'https://github.com/owner/repo/releases/tag/v1.0.0',
            'upload_url' => 'https://uploads.github.com/repos/owner/repo/releases/123/assets{?name,label}',
            'author' => [
                'login' => 'octocat',
                'id' => 1,
                'avatar_url' => 'https://github.com/images/error/octocat_happy.gif',
                'html_url' => 'https://github.com/octocat',
            ],
            'assets' => [],
        ]);

        $responseBody->method('__toString')->willReturn($responseBodyContent);
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateReleaseParams(tagName: 'v1.0.0', name: 'Version 1.0.0');

        $release = $client->createRelease($repo, $params);

        $this->assertInstanceOf(GithubRelease::class, $release);
        $this->assertSame(123, $release->id);
        $this->assertSame('v1.0.0', $release->tagName);
        $this->assertSame('Version 1.0.0', $release->name);
    }

    public function testCreateReleaseThrowsExceptionWithoutStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateReleaseParams(tagName: 'v1.0.0');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for createRelease');

        $client->createRelease($repo, $params);
    }

    public function testCreateReleaseThrowsOn422(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $response->method('getStatusCode')->willReturn(422);
        $response->method('getReasonPhrase')->willReturn('Unprocessable Entity');
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateReleaseParams(tagName: 'v1.0.0');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('422 Unprocessable Entity');

        $client->createRelease($repo, $params);
    }

    public function testGetReleaseByTagSuccess(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $responseBodyStream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $responseBodyContent = json_encode([
            'id' => 456,
            'tag_name' => 'v2.0.0',
            'name' => 'Version 2.0.0',
            'body' => 'Major release',
            'draft' => false,
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
        ]);

        $response->method('getStatusCode')->willReturn(200);
        $responseBodyStream->method('__toString')->willReturn($responseBodyContent);
        $response->method('getBody')->willReturn($responseBodyStream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $release = $client->getReleaseByTag($repo, 'v2.0.0');

        $this->assertInstanceOf(GithubRelease::class, $release);
        $this->assertSame(456, $release->id);
        $this->assertSame('v2.0.0', $release->tagName);
        $this->assertCount(1, $release->assets);
    }

    public function testGetReleaseByTagThrowsOn404(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->getReleaseByTag($repo, 'nonexistent-tag');
    }

    public function testUpdateReleaseSuccess(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $responseBodyStream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $responseBodyContent = json_encode([
            'id' => 123,
            'tag_name' => 'v1.0.1',
            'name' => 'Version 1.0.1',
            'body' => 'Updated release notes',
            'draft' => false,
            'prerelease' => false,
            'created_at' => '2026-01-01T10:00:00Z',
            'published_at' => '2026-01-01T12:00:00Z',
            'html_url' => 'https://github.com/owner/repo/releases/tag/v1.0.1',
            'upload_url' => 'https://uploads.github.com/repos/owner/repo/releases/123/assets{?name,label}',
            'author' => [
                'login' => 'octocat',
                'id' => 1,
                'avatar_url' => 'https://github.com/images/error/octocat_happy.gif',
                'html_url' => 'https://github.com/octocat',
            ],
            'assets' => [],
        ]);

        $response->method('getStatusCode')->willReturn(200);
        $responseBodyStream->method('__toString')->willReturn($responseBodyContent);
        $response->method('getBody')->willReturn($responseBodyStream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new UpdateReleaseParams(body: 'Updated release notes');

        $release = $client->updateRelease($repo, 123, $params);

        $this->assertInstanceOf(GithubRelease::class, $release);
        $this->assertSame(123, $release->id);
        $this->assertSame('Updated release notes', $release->body);
    }

    public function testUpdateReleaseThrowsExceptionWithoutStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new UpdateReleaseParams(draft: false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for updateRelease');

        $client->updateRelease($repo, 123, $params);
    }

    public function testUpdateReleaseThrowsOn404(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new UpdateReleaseParams(name: 'Updated Name');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->updateRelease($repo, 99999, $params);
    }

    public function testUploadReleaseAssetSuccess(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $responseBodyStream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $responseBodyContent = json_encode([
            'id' => 999,
            'name' => 'release.zip',
            'label' => 'Release archive',
            'content_type' => 'application/zip',
            'size' => 4096,
            'download_count' => 0,
            'state' => 'uploaded',
            'browser_download_url' => 'https://github.com/releases/download/v1.0.0/release.zip',
            'created_at' => '2026-01-01T15:00:00Z',
            'updated_at' => '2026-01-01T15:00:00Z',
        ]);

        $response->method('getStatusCode')->willReturn(201);
        $responseBodyStream->method('__toString')->willReturn($responseBodyContent);
        $response->method('getBody')->willReturn($responseBodyStream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $uploadUrl = 'https://uploads.github.com/repos/owner/repo/releases/123/assets{?name,label}';

        $asset = $client->uploadReleaseAsset($uploadUrl, 'release.zip', 'fake-zip-content', 'application/zip');

        $this->assertInstanceOf(GithubReleaseAsset::class, $asset);
        $this->assertSame(999, $asset->id);
        $this->assertSame('release.zip', $asset->name);
        $this->assertSame('application/zip', $asset->contentType);
    }

    public function testUploadReleaseAssetThrowsExceptionWithoutStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $uploadUrl = 'https://uploads.github.com/repos/owner/repo/releases/123/assets{?name,label}';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for uploadReleaseAsset');

        $client->uploadReleaseAsset($uploadUrl, 'file.txt', 'content');
    }

    public function testUploadReleaseAssetThrowsOn422(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $response->method('getStatusCode')->willReturn(422);
        $response->method('getReasonPhrase')->willReturn('Unprocessable Entity');
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $uploadUrl = 'https://uploads.github.com/repos/owner/repo/releases/123/assets{?name,label}';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('422 Unprocessable Entity');

        $client->uploadReleaseAsset($uploadUrl, 'duplicate.zip', 'content');
    }

    public function testUploadReleaseAssetWithDefaultContentType(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $responseBodyStream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $responseBodyContent = json_encode([
            'id' => 888,
            'name' => 'binary.bin',
            'label' => '',
            'content_type' => 'application/octet-stream',
            'size' => 1024,
            'download_count' => 0,
            'state' => 'uploaded',
            'browser_download_url' => 'https://github.com/releases/download/v1.0.0/binary.bin',
            'created_at' => '2026-01-01T16:00:00Z',
            'updated_at' => '2026-01-01T16:00:00Z',
        ]);

        $response->method('getStatusCode')->willReturn(201);
        $responseBodyStream->method('__toString')->willReturn($responseBodyContent);
        $response->method('getBody')->willReturn($responseBodyStream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $uploadUrl = 'https://uploads.github.com/repos/owner/repo/releases/123/assets{?name,label}';

        // Test without specifying contentType (should default to application/octet-stream)
        $asset = $client->uploadReleaseAsset($uploadUrl, 'binary.bin', 'binary-content');

        $this->assertInstanceOf(GithubReleaseAsset::class, $asset);
        $this->assertSame('application/octet-stream', $asset->contentType);
    }
}
