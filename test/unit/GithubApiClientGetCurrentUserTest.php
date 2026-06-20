<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubUser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Exception;

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
class GithubApiClientGetCurrentUserTest extends TestCase
{
    public function testGetCurrentUserSuccess(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $responseBody = json_encode([
            'login' => 'octocat',
            'id' => 1,
            'avatar_url' => 'https://github.com/images/error/octocat_happy.gif',
            'html_url' => 'https://github.com/octocat',
            'type' => 'User',
            'name' => 'The Octocat',
            'email' => 'octocat@github.com',
        ]);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($responseBody);

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $user = $client->getCurrentUser();

        $this->assertInstanceOf(GithubUser::class, $user);
        $this->assertSame('octocat', $user->login);
        $this->assertSame(1, $user->id);
        $this->assertSame('https://github.com/images/error/octocat_happy.gif', $user->avatarUrl);
        $this->assertSame('https://github.com/octocat', $user->htmlUrl);
        $this->assertSame('User', $user->type);
    }

    public function testGetCurrentUserThrowsOn401Unauthorized(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'invalid-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $errorBody = json_encode([
            'message' => 'Bad credentials',
            'documentation_url' => 'https://docs.github.com/rest',
        ]);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($errorBody);

        $response->method('getStatusCode')->willReturn(401);
        $response->method('getReasonPhrase')->willReturn('Unauthorized');
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('401 Unauthorized: Bad credentials');

        $client->getCurrentUser();
    }

    public function testGetCurrentUserThrowsOn403Forbidden(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();

        $errorBody = json_encode([
            'message' => 'Resource not accessible by personal access token',
            'documentation_url' => 'https://docs.github.com/rest',
        ]);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($errorBody);

        $response->method('getStatusCode')->willReturn(403);
        $response->method('getReasonPhrase')->willReturn('Forbidden');
        $response->method('getBody')->willReturn($stream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('403 Forbidden: Resource not accessible by personal access token');

        $client->getCurrentUser();
    }
}
