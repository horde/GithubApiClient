<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\CreateIssueTypeParams;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubIssueType;
use Horde\GithubApiClient\GithubIssueTypeList;
use Horde\GithubApiClient\GithubOrganizationId;
use Horde\GithubApiClient\GithubRepository;
use Horde\GithubApiClient\UpdateIssueTypeParams;
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
final class GithubApiClientIssueTypeTest extends TestCase
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
    private function typeBody(int $id = 9, string $name = 'Bug'): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => 'A defect',
            'color' => 'red',
            'is_enabled' => true,
            'created_at' => '',
            'updated_at' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function issueBody(int $number = 42, ?array $type = null): array
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
            'type' => $type,
            'comments' => 0,
            'created_at' => '',
            'updated_at' => '',
            'closed_at' => null,
        ];
    }

    public function testListIssueTypesSuccess(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn(
            (string) json_encode([$this->typeBody(1, 'Bug'), $this->typeBody(2, 'Feature')])
        );
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $org = new GithubOrganizationId('horde');

        $list = $client->listIssueTypes($org);

        $this->assertInstanceOf(GithubIssueTypeList::class, $list);
        $this->assertCount(2, $list);
        $array = $list->toArray();
        $this->assertSame('Bug', $array[0]->name);
        $this->assertSame('Feature', $array[1]->name);
    }

    public function testListIssueTypesThrowsOn404(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->listIssueTypes(new GithubOrganizationId('missing'));
    }

    public function testCreateIssueTypeSuccess(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->typeBody()));
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);

        $type = $client->createIssueType(
            new GithubOrganizationId('horde'),
            new CreateIssueTypeParams(name: 'Bug')
        );

        $this->assertInstanceOf(GithubIssueType::class, $type);
        $this->assertSame('Bug', $type->name);
    }

    public function testCreateIssueTypeRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'tok');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for createIssueType');

        $client->createIssueType(new GithubOrganizationId('o'), new CreateIssueTypeParams(name: 'Bug'));
    }

    public function testUpdateIssueTypeSuccess(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $body = $this->typeBody();
        $body['color'] = 'green';
        $responseBody->method('__toString')->willReturn((string) json_encode($body));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);

        $type = $client->updateIssueType(
            new GithubOrganizationId('horde'),
            9,
            new UpdateIssueTypeParams(color: 'green')
        );

        $this->assertSame('green', $type->color);
    }

    public function testUpdateIssueTypeRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'tok');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for updateIssueType');

        $client->updateIssueType(new GithubOrganizationId('o'), 9, new UpdateIssueTypeParams());
    }

    public function testDeleteIssueTypeSuccess(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(204);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);

        $client->deleteIssueType(new GithubOrganizationId('horde'), 9);

        $this->addToAssertionCount(1);
    }

    public function testDeleteIssueTypeThrowsOnNon204(): void
    {
        [$httpClient, $requestFactory] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->deleteIssueType(new GithubOrganizationId('horde'), 9999);
    }

    public function testAssignIssueType(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode(
            $this->issueBody(42, type: $this->typeBody(9, 'Bug'))
        ));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $issue = $client->assignIssueType($repo, 42, 'Bug');

        $this->assertNotNull($issue->type);
        $this->assertSame('Bug', $issue->type->name);
    }

    public function testUnassignIssueType(): void
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

        $issue = $client->unassignIssueType($repo, 42);

        $this->assertNull($issue->type);
    }
}
