<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\CreateIssueParams;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubIssue;
use Horde\GithubApiClient\GithubIssueList;
use Horde\GithubApiClient\GithubRepository;
use Horde\GithubApiClient\IssueUpdate;
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
final class GithubApiClientIssueTest extends TestCase
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
    private function issueBody(int $number = 42, bool $isPr = false, ?string $state = 'open'): array
    {
        $body = [
            'id' => $number * 100,
            'number' => $number,
            'title' => 'Issue ' . $number,
            'body' => 'desc',
            'state' => $state,
            'state_reason' => null,
            'html_url' => 'https://github.com/o/r/issues/' . $number,
            'url' => 'https://api.github.com/repos/o/r/issues/' . $number,
            'user' => ['login' => 'reporter', 'id' => 1, 'avatar_url' => '', 'html_url' => ''],
            'labels' => [],
            'assignees' => [],
            'milestone' => null,
            'type' => null,
            'comments' => 0,
            'created_at' => '2026-06-24T10:00:00Z',
            'updated_at' => '2026-06-24T10:00:00Z',
            'closed_at' => null,
        ];
        if ($isPr) {
            $body['pull_request'] = ['url' => 'https://api.github.com/repos/o/r/pulls/' . $number];
        }
        return $body;
    }

    public function testListIssuesSuccess(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode([
            $this->issueBody(1),
            $this->issueBody(2, isPr: true),
        ]));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $list = $client->listIssues($repo);

        $this->assertInstanceOf(GithubIssueList::class, $list);
        $this->assertCount(2, $list);
        $array = $list->toArray();
        $this->assertSame(1, $array[0]->number);
        $this->assertFalse($array[0]->isPullRequest);
        $this->assertTrue($array[1]->isPullRequest);
    }

    public function testListIssuesPassesFilters(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn('[]');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $list = $client->listIssues($repo, state: 'closed', labels: 'bug,urgent', milestone: '7', assignee: 'alice');

        $this->assertCount(0, $list);
    }

    public function testListIssuesThrowsOnNon200(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->listIssues($repo);
    }

    public function testGetIssueSuccess(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->issueBody(42)));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $issue = $client->getIssue($repo, 42);

        $this->assertInstanceOf(GithubIssue::class, $issue);
        $this->assertSame(42, $issue->number);
    }

    public function testGetIssueDetectsPullRequest(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->issueBody(7, isPr: true)));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $issue = $client->getIssue($repo, 7);

        $this->assertTrue($issue->isPullRequest);
    }

    public function testGetIssueThrowsOn404(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->getIssue($repo, 9999);
    }

    public function testCreateIssueSuccess(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->issueBody(42)));
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $issue = $client->createIssue($repo, new CreateIssueParams(title: 'Crash on launch'));

        $this->assertSame(42, $issue->number);
    }

    public function testCreateIssueRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'tok');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for createIssue');

        $client->createIssue($repo, new CreateIssueParams(title: 't'));
    }

    public function testUpdateIssueSuccess(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $body = $this->issueBody(42);
        $body['title'] = 'New title';
        $responseBody->method('__toString')->willReturn((string) json_encode($body));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $issue = $client->updateIssue($repo, 42, (new IssueUpdate())->withTitle('New title'));

        $this->assertSame('New title', $issue->title);
    }

    public function testUpdateIssueRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'tok');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for updateIssue');

        $client->updateIssue($repo, 42, new IssueUpdate());
    }

    public function testCloseIssueSendsClosedState(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->issueBody(42, state: 'closed')));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $issue = $client->closeIssue($repo, 42);

        $this->assertSame('closed', $issue->state);
    }

    public function testReopenIssueSendsOpenState(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->issueBody(42, state: 'open')));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $issue = $client->reopenIssue($repo, 42);

        $this->assertSame('open', $issue->state);
    }
}
