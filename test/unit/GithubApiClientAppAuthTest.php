<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\InstallationAccessToken;
use Horde\GithubApiClient\CreateInstallationAccessTokenParams;
use Horde\GithubApiClient\GithubInstallationList;
use Horde\GithubApiClient\GithubApp;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
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
class GithubApiClientAppAuthTest extends TestCase
{
    // Tests for createInstallationAccessToken

    public function testCreateInstallationAccessTokenSuccess(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'test-jwt-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($bodyStream);

        $responseBody = json_encode([
            'token' => 'ghs_16C7e42F292c6912E7710c838347Ae178B4a',
            'expires_at' => '2026-03-02T13:00:00Z',
            'permissions' => ['contents' => 'read', 'metadata' => 'read'],
            'repository_selection' => 'all'
        ]);

        $stream->method('__toString')->willReturn($responseBody);
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $token = $client->createInstallationAccessToken(12345);

        $this->assertInstanceOf(InstallationAccessToken::class, $token);
        $this->assertSame('ghs_16C7e42F292c6912E7710c838347Ae178B4a', $token->token);
        $this->assertSame('2026-03-02T13:00:00Z', $token->expiresAt);
        $this->assertSame('all', $token->repositorySelection);
    }

    public function testCreateInstallationAccessTokenWithOptionalParameters(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'test-jwt-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($bodyStream);

        $responseBody = json_encode([
            'token' => 'ghs_specific_repos',
            'expires_at' => '2026-03-02T14:00:00Z',
            'permissions' => ['contents' => 'write', 'issues' => 'write'],
            'repository_selection' => 'selected'
        ]);

        $stream->method('__toString')->willReturn($responseBody);
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $params = new CreateInstallationAccessTokenParams(
            repositories: ['repo1', 'repo2'],
            permissions: ['contents' => 'write', 'issues' => 'write']
        );

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $token = $client->createInstallationAccessToken(67890, $params);

        $this->assertInstanceOf(InstallationAccessToken::class, $token);
        $this->assertSame('ghs_specific_repos', $token->token);
        $this->assertSame('selected', $token->repositorySelection);
    }

    public function testCreateInstallationAccessTokenRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'test-jwt-token');

        $client = new GithubApiClient($httpClient, $requestFactory, $config, null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for createInstallationAccessToken');

        $client->createInstallationAccessToken(12345);
    }

    public function testCreateInstallationAccessTokenThrowsOn401Unauthorized(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'invalid-jwt');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($bodyStream);

        $errorBody = json_encode(['message' => 'Bad credentials']);
        $stream->method('__toString')->willReturn($errorBody);
        $response->method('getStatusCode')->willReturn(401);
        $response->method('getReasonPhrase')->willReturn('Unauthorized');
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('401 Unauthorized');

        $client->createInstallationAccessToken(12345);
    }

    public function testCreateInstallationAccessTokenThrowsOn403Forbidden(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'test-jwt');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($bodyStream);

        $errorBody = json_encode(['message' => 'Forbidden']);
        $stream->method('__toString')->willReturn($errorBody);
        $response->method('getStatusCode')->willReturn(403);
        $response->method('getReasonPhrase')->willReturn('Forbidden');
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('403 Forbidden');

        $client->createInstallationAccessToken(12345);
    }

    public function testCreateInstallationAccessTokenThrowsOn404NotFound(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'test-jwt');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($bodyStream);

        $errorBody = json_encode(['message' => 'Not Found']);
        $stream->method('__toString')->willReturn($errorBody);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->createInstallationAccessToken(99999);
    }

    // Tests for listInstallations

    public function testListInstallationsSuccess(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'test-jwt-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $responseBody = json_encode([
            [
                'id' => 111,
                'account' => ['login' => 'org1', 'id' => 1, 'avatar_url' => '', 'html_url' => '', 'type' => 'Organization'],
                'repository_selection' => 'all',
                'created_at' => '2026-01-01T00:00:00Z',
                'updated_at' => '2026-01-01T00:00:00Z'
            ],
            [
                'id' => 222,
                'account' => ['login' => 'org2', 'id' => 2, 'avatar_url' => '', 'html_url' => '', 'type' => 'Organization'],
                'repository_selection' => 'selected',
                'created_at' => '2026-01-02T00:00:00Z',
                'updated_at' => '2026-01-02T00:00:00Z'
            ]
        ]);

        $stream->method('__toString')->willReturn($responseBody);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $installations = $client->listInstallations();

        $this->assertInstanceOf(GithubInstallationList::class, $installations);
        $this->assertCount(2, $installations);

        $installationArray = $installations->toArray();
        $this->assertSame(111, $installationArray[0]->id);
        $this->assertSame('org1', $installationArray[0]->account->login);
        $this->assertSame(222, $installationArray[1]->id);
        $this->assertSame('org2', $installationArray[1]->account->login);
    }

    public function testListInstallationsEmptyArray(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'test-jwt-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $stream->method('__toString')->willReturn('[]');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $installations = $client->listInstallations();

        $this->assertInstanceOf(GithubInstallationList::class, $installations);
        $this->assertCount(0, $installations);
    }

    public function testListInstallationsMultipleInstallations(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'test-jwt-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $installationsData = [];
        for ($i = 1; $i <= 5; $i++) {
            $installationsData[] = [
                'id' => $i * 100,
                'account' => ['login' => "org{$i}", 'id' => $i, 'avatar_url' => '', 'html_url' => '', 'type' => 'Organization'],
                'repository_selection' => 'all',
                'created_at' => '2026-01-01T00:00:00Z',
                'updated_at' => '2026-01-01T00:00:00Z'
            ];
        }

        $stream->method('__toString')->willReturn(json_encode($installationsData));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $installations = $client->listInstallations();

        $this->assertCount(5, $installations);
    }

    public function testListInstallationsErrorHandling(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'invalid-jwt');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $stream->method('__toString')->willReturn(json_encode(['message' => 'Unauthorized']));
        $response->method('getStatusCode')->willReturn(401);
        $response->method('getReasonPhrase')->willReturn('Unauthorized');
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('401 Unauthorized');

        $client->listInstallations();
    }

    // Tests for getAuthenticatedApp

    public function testGetAuthenticatedAppSuccess(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'test-jwt-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $responseBody = json_encode([
            'id' => 123456,
            'slug' => 'my-github-app',
            'name' => 'My GitHub App',
            'owner' => [
                'login' => 'app-owner',
                'id' => 789,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/789',
                'html_url' => 'https://github.com/app-owner',
                'type' => 'User'
            ],
            'created_at' => '2025-01-01T00:00:00Z',
            'updated_at' => '2026-03-01T00:00:00Z'
        ]);

        $stream->method('__toString')->willReturn($responseBody);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $app = $client->getAuthenticatedApp();

        $this->assertInstanceOf(GithubApp::class, $app);
        $this->assertSame(123456, $app->id);
        $this->assertSame('my-github-app', $app->slug);
        $this->assertSame('My GitHub App', $app->name);
        $this->assertSame('app-owner', $app->owner->login);
    }

    public function testGetAuthenticatedAppErrorHandling(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(jwt: 'invalid-jwt');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $stream->method('__toString')->willReturn(json_encode(['message' => 'Bad credentials']));
        $response->method('getStatusCode')->willReturn(401);
        $response->method('getReasonPhrase')->willReturn('Unauthorized');
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('401 Unauthorized');

        $client->getAuthenticatedApp();
    }
}
