<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\CreatePullRequestParams;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[CoversClass(GithubApiClient::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubApiClientErrorHandlingTest extends TestCase
{
    public function testCreatePullRequestThrowsOn422UnprocessableEntity(): void
    {
        // This test covers the case where:
        // - The branch doesn't exist on remote
        // - The PR already exists
        // - There are validation errors in the request

        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        // Mock the request creation chain
        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        // Mock 422 Unprocessable Entity response
        $response->method('getStatusCode')->willReturn(422);
        $response->method('getReasonPhrase')->willReturn('Unprocessable Entity');
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('horde/githubapiclient');
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'non-existent-branch',
            base: 'main'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('422 Unprocessable Entity');

        $client->createPullRequest($repo, $params);
    }

    public function testCreatePullRequestThrowsOn404NotFound(): void
    {
        // This test covers the case where the repository doesn't exist

        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('nonexistent/repo');
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'feature',
            base: 'main'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->createPullRequest($repo, $params);
    }

    public function testCreatePullRequestThrowsOn401Unauthorized(): void
    {
        // This test covers the case where the token is invalid or expired

        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'invalid-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $response->method('getStatusCode')->willReturn(401);
        $response->method('getReasonPhrase')->willReturn('Unauthorized');
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('horde/githubapiclient');
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'feature',
            base: 'main'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('401 Unauthorized');

        $client->createPullRequest($repo, $params);
    }

    public function testCreatePullRequestThrowsOn403Forbidden(): void
    {
        // This test covers the case where the token lacks necessary permissions

        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        $response->method('getStatusCode')->willReturn(403);
        $response->method('getReasonPhrase')->willReturn('Forbidden');
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('horde/githubapiclient');
        $params = new CreatePullRequestParams(
            title: 'Test PR',
            head: 'feature',
            base: 'main'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('403 Forbidden');

        $client->createPullRequest($repo, $params);
    }

    public function testCreateReviewThrowsDetailedErrorOn422(): void
    {
        // This test covers the improved error handling that includes GitHub's detailed error messages

        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'test-token');

        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $streamFactory->method('createStream')->willReturn($stream);

        // Mock 422 response with detailed GitHub error message
        $response->method('getStatusCode')->willReturn(422);
        $response->method('getReasonPhrase')->willReturn('Unprocessable Entity');
        $errorBody = json_encode([
            'message' => 'Unprocessable Entity',
            'errors' => ['Review Can not approve your own pull request'],
            'documentation_url' => 'https://docs.github.com/rest/pulls/reviews#create-a-review-for-a-pull-request'
        ]);
        $errorStream = $this->createMock(StreamInterface::class);
        $errorStream->method('__toString')->willReturn($errorBody);
        $response->method('getBody')->willReturn($errorStream);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = \Horde\GithubApiClient\GithubRepository::fromFullName('horde/hordectl');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('422 Unprocessable Entity: Review Can not approve your own pull request');

        $params = new \Horde\GithubApiClient\CreateReviewParams(event: 'APPROVE', body: '');
        $client->createReview($repo, 1, $params);
    }
}
