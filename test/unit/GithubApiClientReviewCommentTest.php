<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\CreateReviewCommentParams;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubRepository;
use Horde\GithubApiClient\GithubReviewComment;
use Horde\GithubApiClient\GithubReviewCommentList;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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
#[AllowMockObjectsWithoutExpectations]
class GithubApiClientReviewCommentTest extends TestCase
{
    /**
     * @return array{ClientInterface, RequestFactoryInterface, StreamFactoryInterface, RequestInterface}
     */
    private function makeWriteMocks(): array
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        return [$httpClient, $requestFactory, $streamFactory, $request];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewCommentBody(int $id = 77): array
    {
        return [
            'id' => $id,
            'body' => 'Found it',
            'path' => 'src/Foo.php',
            'line' => 12,
            'start_line' => null,
            'side' => 'RIGHT',
            'commit_id' => 'sha-abc',
            'user' => ['login' => 'octocat'],
            'html_url' => 'https://example.org/r/' . $id,
            'created_at' => '2026-06-24T10:00:00Z',
            'updated_at' => '2026-06-24T10:00:00Z',
        ];
    }

    public function testCreateReviewCommentSuccess(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->reviewCommentBody()));
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateReviewCommentParams(
            body: 'Found it',
            commitId: 'sha-abc',
            path: 'src/Foo.php',
            line: 12,
        );

        $comment = $client->createReviewComment($repo, 1, $params);

        $this->assertInstanceOf(GithubReviewComment::class, $comment);
        $this->assertSame(77, $comment->id);
        $this->assertSame('src/Foo.php', $comment->path);
    }

    public function testCreateReviewCommentRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'tok');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateReviewCommentParams(body: 'x', commitId: 's', path: 'p', line: 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for createReviewComment');

        $client->createReviewComment($repo, 1, $params);
    }

    public function testCreateReviewCommentThrowsOn422(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(422);
        $response->method('getReasonPhrase')->willReturn('Unprocessable Entity');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateReviewCommentParams(body: 'x', commitId: 's', path: 'p', line: 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('422 Unprocessable Entity');

        $client->createReviewComment($repo, 1, $params);
    }

    public function testListReviewCommentsSuccess(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $payload = [$this->reviewCommentBody(1), $this->reviewCommentBody(2)];
        $responseBody->method('__toString')->willReturn((string) json_encode($payload));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $list = $client->listReviewComments($repo, 1);

        $this->assertInstanceOf(GithubReviewCommentList::class, $list);
        $this->assertCount(2, $list);
        $array = $list->toArray();
        $this->assertSame(1, $array[0]->id);
        $this->assertSame(2, $array[1]->id);
    }

    public function testListReviewCommentsThrowsOn404(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->listReviewComments($repo, 9999);
    }

    public function testUpdateReviewCommentSuccess(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $body = $this->reviewCommentBody();
        $body['body'] = 'Edited body';
        $responseBody->method('__toString')->willReturn((string) json_encode($body));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $comment = $client->updateReviewComment($repo, 77, 'Edited body');

        $this->assertSame('Edited body', $comment->body);
    }

    public function testUpdateReviewCommentRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'tok');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for updateReviewComment');

        $client->updateReviewComment($repo, 77, 'new body');
    }

    public function testDeleteReviewCommentSuccess(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $response->method('getStatusCode')->willReturn(204);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $client->deleteReviewComment($repo, 77);

        $this->addToAssertionCount(1);
    }

    public function testDeleteReviewCommentThrowsOnNon204(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->deleteReviewComment($repo, 99999);
    }
}
