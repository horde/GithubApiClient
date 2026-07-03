<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubRelease;
use Horde\GithubApiClient\GithubReleaseList;
use Horde\GithubApiClient\GithubRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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
#[AllowMockObjectsWithoutExpectations]
final class GithubApiClientListReleasesTest extends TestCase
{
    /**
     * @return array{ClientInterface, RequestFactoryInterface, RequestInterface}
     */
    private function makeReadMocks(): array
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        return [$httpClient, $requestFactory, $request];
    }

    /**
     * @return array<string, mixed>
     */
    private function releaseBody(int $id, string $tag): array
    {
        return [
            'id' => $id,
            'node_id' => 'RE_kwDO' . $id,
            'tag_name' => $tag,
            'name' => $tag,
            'body' => 'Release notes for ' . $tag,
            'draft' => false,
            'prerelease' => false,
            'created_at' => '2026-07-01T12:00:00Z',
            'published_at' => '2026-07-01T12:00:00Z',
            'html_url' => 'https://github.com/owner/repo/releases/tag/' . $tag,
            'upload_url' => 'https://uploads.example/' . $id,
            'author' => [
                'login' => 'octocat',
                'id' => 1,
                'node_id' => 'MDQ6VXNlcjE=',
                'html_url' => 'https://example.org/octocat',
                'avatar_url' => '',
            ],
            'assets' => [],
        ];
    }

    private function stubResponse(array $releases, string $linkHeader = ''): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn((string) json_encode($releases));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);
        // The pagination helper reads the Link header off the response
        // to decide whether to keep going.  An empty header ends the loop.
        $response->method('getHeaderLine')->willReturnCallback(
            static fn (string $name): string => strtolower($name) === 'link' ? $linkHeader : ''
        );
        $response->method('hasHeader')->willReturnCallback(
            static fn (string $name): bool => strtolower($name) === 'link' && $linkHeader !== ''
        );
        return $response;
    }

    public function testListReleasesEmptyPage(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $httpClient->method('sendRequest')->willReturn($this->stubResponse([]));

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('horde/example');

        $releases = $client->listReleases($repo);

        $this->assertInstanceOf(GithubReleaseList::class, $releases);
        $this->assertCount(0, $releases);
    }

    public function testListReleasesReturnsHydratedEntities(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $httpClient->method('sendRequest')->willReturn($this->stubResponse([
            $this->releaseBody(1, 'v1.0.0'),
            $this->releaseBody(2, 'v1.1.0'),
        ]));

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('horde/example');

        $releases = $client->listReleases($repo);

        $this->assertCount(2, $releases);
        $items = $releases->toArray();
        $this->assertInstanceOf(GithubRelease::class, $items[0]);
        $this->assertSame('v1.0.0', $items[0]->tagName);
        $this->assertSame('v1.1.0', $items[1]->tagName);
    }

    public function testListReleasesRaisesOnNon200(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn('{"message":"Not Found"}');
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getBody')->willReturn($body);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('horde/does-not-exist');

        $this->expectException(Exception::class);
        $client->listReleases($repo);
    }
}
