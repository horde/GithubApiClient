<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubComment;
use Horde\GithubApiClient\GithubCommentList;
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
final class GithubApiClientListIssueCommentsTest extends TestCase
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
    private function commentBody(int $id, string $body): array
    {
        return [
            'id' => $id,
            'node_id' => 'IC_kwDO' . $id,
            'body' => $body,
            'html_url' => 'https://example.org/comment/' . $id,
            'user' => [
                'login' => 'octocat',
                'id' => 1,
                'node_id' => 'MDQ6VXNlcjE=',
                'html_url' => 'https://example.org/octocat',
                'avatar_url' => '',
            ],
            'created_at' => '2026-06-25T12:00:00Z',
            'updated_at' => '2026-06-25T12:00:00Z',
        ];
    }

    private function stubResponse(array $comments, string $linkHeader = ''): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn((string) json_encode($comments));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);
        $response->method('getHeaderLine')->willReturnCallback(
            static fn (string $name): string => strtolower($name) === 'link' ? $linkHeader : ''
        );
        $response->method('hasHeader')->willReturnCallback(
            static fn (string $name): bool => strtolower($name) === 'link' && $linkHeader !== ''
        );
        return $response;
    }

    public function testRepoWideListing(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $httpClient->method('sendRequest')->willReturn($this->stubResponse([
            $this->commentBody(1, 'first'),
            $this->commentBody(2, 'second'),
        ]));

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('horde/example');

        $comments = $client->listIssueComments($repo);

        $this->assertInstanceOf(GithubCommentList::class, $comments);
        $this->assertCount(2, $comments);
        $items = $comments->toArray();
        $this->assertInstanceOf(GithubComment::class, $items[0]);
        $this->assertSame('first', $items[0]->body);
    }

    public function testSingleIssueListing(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $httpClient->method('sendRequest')->willReturn($this->stubResponse([
            $this->commentBody(11, 'on-30-a'),
            $this->commentBody(12, 'on-30-b'),
        ]));

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('horde/example');

        $comments = $client->listIssueComments($repo, issueNumber: 30);

        $this->assertCount(2, $comments);
    }

    public function testEmptyPage(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $httpClient->method('sendRequest')->willReturn($this->stubResponse([]));

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('horde/example');

        $comments = $client->listIssueComments($repo);
        $this->assertCount(0, $comments);
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
        $client->listIssueComments($repo);
    }
}
