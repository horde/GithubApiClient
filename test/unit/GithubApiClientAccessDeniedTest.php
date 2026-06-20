<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\CreateCheckRunParams;
use Horde\GithubApiClient\GithubApiAccessDeniedException;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubRepository;
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
 * Verifies that a 403 response with the GitHub Actions integration body
 * surfaces a typed exception, while non-matching 403s and non-403s still
 * fall through to the plain Exception path. Uses createCheckRun as the
 * representative write method; all writes share the same helper.
 *
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
#[CoversClass(GithubApiAccessDeniedException::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubApiClientAccessDeniedTest extends TestCase
{
    /**
     * @return array{ClientInterface, RequestFactoryInterface, StreamFactoryInterface, ResponseInterface, StreamInterface}
     */
    private function makeMocksFor403(string $bodyJson): array
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $responseBody->method('__toString')->willReturn($bodyJson);
        $response->method('getStatusCode')->willReturn(403);
        $response->method('getReasonPhrase')->willReturn('Forbidden');
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        return [$httpClient, $requestFactory, $streamFactory, $response, $responseBody];
    }

    public function testThrowsTypedExceptionOn403WithMatchingBody(): void
    {
        $body = (string) json_encode([
            'message' => 'Resource not accessible by integration',
            'documentation_url' => 'https://docs.github.com/rest',
        ]);

        [$httpClient, $requestFactory, $streamFactory] = $this->makeMocksFor403($body);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateCheckRunParams(name: 'PHPUnit', headSha: 'abc');

        try {
            $client->createCheckRun($repo, $params);
            $this->fail('Expected GithubApiAccessDeniedException');
        } catch (GithubApiAccessDeniedException $e) {
            $this->assertSame(403, $e->statusCode);
            $this->assertSame($body, $e->responseBody);
            $this->assertNotSame('', $e->hint);
            $this->assertStringContainsString('permission', $e->hint);
        }
    }

    public function testFallsThroughToPlainExceptionOn403WithOtherBody(): void
    {
        $body = (string) json_encode([
            'message' => 'API rate limit exceeded',
            'documentation_url' => 'https://docs.github.com/rest',
        ]);

        [$httpClient, $requestFactory, $streamFactory] = $this->makeMocksFor403($body);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateCheckRunParams(name: 'PHPUnit', headSha: 'abc');

        try {
            $client->createCheckRun($repo, $params);
            $this->fail('Expected exception');
        } catch (GithubApiAccessDeniedException $e) {
            $this->fail('Should not throw the typed exception for non-matching 403 body');
        } catch (Exception $e) {
            $this->assertStringContainsString('403', $e->getMessage());
            $this->assertStringContainsString('rate limit', $e->getMessage());
        }
    }

    public function testFallsThroughOn403WithEmptyBody(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeMocksFor403('');

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateCheckRunParams(name: 'PHPUnit', headSha: 'abc');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('403 Forbidden');

        $client->createCheckRun($repo, $params);
    }

    public function testNon403FallsThroughUnchanged(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $response->method('getStatusCode')->willReturn(422);
        $response->method('getReasonPhrase')->willReturn('Unprocessable Entity');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateCheckRunParams(name: 'PHPUnit', headSha: 'abc');

        try {
            $client->createCheckRun($repo, $params);
            $this->fail('Expected exception');
        } catch (GithubApiAccessDeniedException $e) {
            $this->fail('Should not throw the typed exception for non-403');
        } catch (Exception $e) {
            $this->assertStringContainsString('422', $e->getMessage());
        }
    }
}
