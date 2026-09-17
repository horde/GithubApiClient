<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubRepository;
use Horde\GithubApiClient\GithubTimelineEvent;
use Horde\GithubApiClient\GithubTimelineEventList;
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
final class GithubApiClientGetIssueTimelineTest extends TestCase
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

    private function stubResponse(array $items, string $linkHeader = ''): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn((string) json_encode($items));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);
        $response->method('getHeaderLine')->willReturnCallback(
            static fn(string $name): string => strtolower($name) === 'link' ? $linkHeader : ''
        );
        $response->method('hasHeader')->willReturnCallback(
            static fn(string $name): bool => strtolower($name) === 'link' && $linkHeader !== ''
        );
        return $response;
    }

    public function testListsTimelineItemsOfDifferentShapes(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $httpClient->method('sendRequest')->willReturn($this->stubResponse([
            [
                'id' => 1,
                'node_id' => 'TE_1',
                'event' => 'commented',
                'body' => 'nice work',
                'actor' => [
                    'login' => 'octocat',
                    'id' => 1,
                    'html_url' => 'https://example.org/octocat',
                    'avatar_url' => '',
                ],
                'created_at' => '2026-06-25T12:00:00Z',
            ],
            [
                'event' => 'committed',
                'sha' => 'abc123',
                'message' => 'fix bug',
            ],
        ]));

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('horde/example');

        $timeline = $client->getIssueTimeline($repo, 30);

        $this->assertInstanceOf(GithubTimelineEventList::class, $timeline);
        $this->assertCount(2, $timeline);
        $items = $timeline->toArray();
        $this->assertInstanceOf(GithubTimelineEvent::class, $items[0]);
        $this->assertSame('commented', $items[0]->event);
        $this->assertSame('nice work', $items[0]->raw['body']);
        $this->assertSame('committed', $items[1]->event);
        $this->assertNull($items[1]->actor);
        $this->assertSame('abc123', $items[1]->raw['sha']);
    }

    public function testRaisesOnNon200(): void
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
        $client->getIssueTimeline($repo, 30);
    }
}
