<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\GithubApiAccessDeniedException;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubRepository;
use Horde\GithubApiClient\RepositoryUpdate;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(GithubApiClient::class)]
#[CoversClass(RepositoryUpdate::class)]
#[CoversClass(GithubApiAccessDeniedException::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubApiClientRepositoryTest extends TestCase
{
    /**
     * @return array{0:ClientInterface,1:RequestFactoryInterface,2:StreamFactoryInterface}
     */
    private function mocks(int $status, string $bodyJson): array
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
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getReasonPhrase')->willReturn($status === 200 ? 'OK' : 'Forbidden');
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        return [$httpClient, $requestFactory, $streamFactory];
    }

    private function client(ClientInterface $http, RequestFactoryInterface $rf, StreamFactoryInterface $sf): GithubApiClient
    {
        return new GithubApiClient($http, $rf, new GithubApiConfig(accessToken: 'tok'), $sf);
    }

    private function repo(): GithubRepository
    {
        return GithubRepository::fromFullName('horde/Victim');
    }

    public function testGetRepositoryReturnsDefaultBranch(): void
    {
        [$http, $rf, $sf] = $this->mocks(200, '{"name":"Victim","default_branch":"main"}');
        $data = $this->client($http, $rf, $sf)->getRepository($this->repo());
        $this->assertSame('main', $data->default_branch);
    }

    public function testUpdateRepositorySetsDefaultBranch(): void
    {
        [$http, $rf, $sf] = $this->mocks(200, '{"name":"Victim","default_branch":"FRAMEWORK_6_0"}');
        $data = $this->client($http, $rf, $sf)->updateRepository(
            $this->repo(),
            new RepositoryUpdate(defaultBranch: 'FRAMEWORK_6_0')
        );
        $this->assertSame('FRAMEWORK_6_0', $data->default_branch);
    }

    public function testUpdateRepositoryForbiddenRaisesTypedException(): void
    {
        [$http, $rf, $sf] = $this->mocks(403, '{"message":"Must have admin rights to Repository."}');
        try {
            $this->client($http, $rf, $sf)->updateRepository(
                $this->repo(),
                new RepositoryUpdate(defaultBranch: 'FRAMEWORK_6_0')
            );
            $this->fail('Expected GithubApiAccessDeniedException');
        } catch (GithubApiAccessDeniedException $e) {
            $this->assertSame(403, $e->statusCode);
            $this->assertStringContainsString('settings/branches', $e->hint);
        }
    }

    public function testUpdateRepositoryNotFoundIsTreatedAsInsufficientPermission(): void
    {
        [$http, $rf, $sf] = $this->mocks(404, '{"message":"Not Found"}');
        $this->expectException(GithubApiAccessDeniedException::class);
        $this->client($http, $rf, $sf)->updateRepository(
            $this->repo(),
            new RepositoryUpdate(defaultBranch: 'FRAMEWORK_6_0')
        );
    }

    public function testRepositoryUpdateToArrayOnlyIncludesSetFields(): void
    {
        $this->assertSame(['default_branch' => 'X'], (new RepositoryUpdate(defaultBranch: 'X'))->toArray());
        $this->assertTrue((new RepositoryUpdate())->isEmpty());
    }
}
