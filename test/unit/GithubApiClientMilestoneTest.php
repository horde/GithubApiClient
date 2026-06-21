<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\CreateMilestoneParams;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubMilestone;
use Horde\GithubApiClient\GithubMilestoneList;
use Horde\GithubApiClient\GithubRepository;
use Horde\GithubApiClient\UpdateMilestoneParams;
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
final class GithubApiClientMilestoneTest extends TestCase
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
    private function milestoneBody(int $number = 3, string $title = 'v2'): array
    {
        return [
            'id' => 10,
            'number' => $number,
            'title' => $title,
            'description' => '',
            'state' => 'open',
            'creator' => null,
            'open_issues' => 0,
            'closed_issues' => 0,
            'html_url' => '',
            'created_at' => '',
            'updated_at' => '',
            'closed_at' => null,
            'due_on' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function issueBody(int $number = 42): array
    {
        return [
            'id' => 1,
            'number' => $number,
            'title' => 't',
            'body' => '',
            'state' => 'open',
            'html_url' => '',
            'url' => '',
            'user' => ['login' => 'u', 'id' => 1, 'avatar_url' => '', 'html_url' => ''],
            'labels' => [],
            'assignees' => [],
            'milestone' => null,
            'type' => null,
            'comments' => 0,
            'created_at' => '',
            'updated_at' => '',
            'closed_at' => null,
        ];
    }

    public function testListMilestonesSuccess(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn(
            (string) json_encode([$this->milestoneBody(1, 'v1'), $this->milestoneBody(2, 'v2')])
        );
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $list = $client->listMilestones($repo);

        $this->assertInstanceOf(GithubMilestoneList::class, $list);
        $this->assertCount(2, $list);
    }

    public function testGetMilestoneSuccess(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->milestoneBody(3, 'v2')));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $milestone = $client->getMilestone($repo, 3);

        $this->assertSame('v2', $milestone->title);
    }

    public function testGetMilestoneThrowsOn404(): void
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

        $client->getMilestone($repo, 9999);
    }

    public function testCreateMilestoneSuccess(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->milestoneBody()));
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $milestone = $client->createMilestone($repo, new CreateMilestoneParams(title: 'v2'));

        $this->assertInstanceOf(GithubMilestone::class, $milestone);
    }

    public function testCreateMilestoneRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'tok');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for createMilestone');

        $client->createMilestone($repo, new CreateMilestoneParams(title: 'v2'));
    }

    public function testUpdateMilestoneSuccess(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $body = $this->milestoneBody();
        $body['state'] = 'closed';
        $responseBody->method('__toString')->willReturn((string) json_encode($body));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $milestone = $client->updateMilestone($repo, 3, new UpdateMilestoneParams(state: 'closed'));

        $this->assertSame('closed', $milestone->state);
    }

    public function testUpdateMilestoneRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'tok');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for updateMilestone');

        $client->updateMilestone($repo, 3, new UpdateMilestoneParams());
    }

    public function testDeleteMilestoneSuccess(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(204);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $client->deleteMilestone($repo, 3);

        $this->addToAssertionCount(1);
    }

    public function testDeleteMilestoneThrowsOnNon204(): void
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

        $client->deleteMilestone($repo, 9999);
    }

    public function testAssignMilestone(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $body = $this->issueBody(42);
        $body['milestone'] = $this->milestoneBody(3, 'v2');
        $responseBody->method('__toString')->willReturn((string) json_encode($body));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $issue = $client->assignMilestone($repo, 42, 3);

        $this->assertNotNull($issue->milestone);
        $this->assertSame('v2', $issue->milestone->title);
    }

    public function testUnassignMilestone(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->issueBody(42)));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $issue = $client->unassignMilestone($repo, 42);

        $this->assertNull($issue->milestone);
    }
}
