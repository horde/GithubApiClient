<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubRepository;
use Horde\GithubApiClient\GithubRepositoryEvent;
use Horde\GithubApiClient\GithubRepositoryEventList;
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
final class GithubApiClientGetRepositoryEventsTest extends TestCase
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
    private function eventBody(string $id, string $type): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'actor' => [
                'login' => 'octocat',
                'id' => 1,
                'avatar_url' => '',
                'url' => 'https://example.org/api/users/octocat',
            ],
            'repo' => [
                'id' => 1,
                'name' => 'horde/example',
                'url' => 'https://example.org/api/repos/horde/example',
            ],
            'payload' => ['action' => 'opened'],
            'public' => true,
            'created_at' => '2026-06-25T12:00:00Z',
        ];
    }

    private function stubResponse(array $events, string $linkHeader = ''): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn((string) json_encode($events));
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

    public function testListsRepositoryEvents(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $httpClient->method('sendRequest')->willReturn($this->stubResponse([
            $this->eventBody('1', 'IssuesEvent'),
            $this->eventBody('2', 'PushEvent'),
        ]));

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('horde/example');

        $events = $client->getRepositoryEvents($repo);

        $this->assertInstanceOf(GithubRepositoryEventList::class, $events);
        $this->assertCount(2, $events);
        $items = $events->toArray();
        $this->assertInstanceOf(GithubRepositoryEvent::class, $items[0]);
        $this->assertSame('IssuesEvent', $items[0]->type);
        $this->assertSame('horde/example', $items[0]->repoName);
        $this->assertSame('opened', $items[0]->payload['action']);
        $this->assertTrue($items[0]->public);
    }

    public function testEmptyPage(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $httpClient->method('sendRequest')->willReturn($this->stubResponse([]));

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('horde/example');

        $events = $client->getRepositoryEvents($repo);
        $this->assertCount(0, $events);
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
        $client->getRepositoryEvents($repo);
    }
}
