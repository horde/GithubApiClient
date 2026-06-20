<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Exception;
use Horde\GithubApiClient\CreateLabelParams;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\GithubLabel;
use Horde\GithubApiClient\GithubLabelList;
use Horde\GithubApiClient\GithubRepository;
use Horde\GithubApiClient\UpdateLabelParams;
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
class GithubApiClientRepositoryLabelsTest extends TestCase
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
     * @return array<string, string>
     */
    private function labelBody(string $name = 'bug'): array
    {
        return ['name' => $name, 'color' => 'd73a4a', 'description' => 'Something broken'];
    }

    public function testListRepositoryLabelsSuccessSinglePage(): void
    {
        [$httpClient, $requestFactory, $request] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode(
            [$this->labelBody('bug'), $this->labelBody('enhancement')]
        ));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        // Empty Link header → pagination sees no next, loop exits after one round.
        $response->method('getHeaderLine')->willReturn('');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $list = $client->listRepositoryLabels($repo);

        $this->assertInstanceOf(GithubLabelList::class, $list);
        $this->assertCount(2, $list);
        $array = $list->toArray();
        $this->assertSame('bug', $array[0]->name);
        $this->assertSame('enhancement', $array[1]->name);
    }

    public function testListRepositoryLabelsThrowsOnNon200(): void
    {
        [$httpClient, $requestFactory, $request] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->listRepositoryLabels($repo);
    }

    public function testGetLabelSuccess(): void
    {
        [$httpClient, $requestFactory, $request] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->labelBody('bug')));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $label = $client->getLabel($repo, 'bug');

        $this->assertInstanceOf(GithubLabel::class, $label);
        $this->assertSame('bug', $label->name);
    }

    public function testGetLabelWithSpacesAndSlashesInName(): void
    {
        // Names like "type: bug/regression" must round-trip; rawurlencode applies
        // in the factory. Verified by reaching the success branch without error.
        [$httpClient, $requestFactory, $request] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode(
            $this->labelBody('type: bug/regression')
        ));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $label = $client->getLabel($repo, 'type: bug/regression');

        $this->assertSame('type: bug/regression', $label->name);
    }

    public function testGetLabelThrowsOn404(): void
    {
        [$httpClient, $requestFactory, $request] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->getLabel($repo, 'missing');
    }

    public function testCreateLabelSuccess(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->labelBody('bug')));
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');
        $params = new CreateLabelParams(name: 'bug', color: 'd73a4a', description: 'Something broken');

        $label = $client->createLabel($repo, $params);

        $this->assertSame('bug', $label->name);
        $this->assertSame('d73a4a', $label->color);
    }

    public function testCreateLabelRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'tok');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for createLabel');

        $client->createLabel($repo, new CreateLabelParams(name: 'x', color: 'fff'));
    }

    public function testCreateLabelThrowsOn422(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(422);
        $response->method('getReasonPhrase')->willReturn('Unprocessable Entity');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('422 Unprocessable Entity');

        $client->createLabel($repo, new CreateLabelParams(name: 'dup', color: 'fff'));
    }

    public function testUpdateLabelSuccessRename(): void
    {
        [$httpClient, $requestFactory, $streamFactory] = $this->makeWriteMocks();
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody->method('__toString')->willReturn((string) json_encode($this->labelBody('critical-bug')));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config, $streamFactory);
        $repo = GithubRepository::fromFullName('owner/repo');

        // URL key 'bug' looks up; body name 'critical-bug' renames.
        $label = $client->updateLabel($repo, 'bug', new UpdateLabelParams(name: 'critical-bug'));

        $this->assertSame('critical-bug', $label->name);
    }

    public function testUpdateLabelRequiresStreamFactory(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $config = new GithubApiConfig(accessToken: 'tok');

        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('StreamFactory is required for updateLabel');

        $client->updateLabel($repo, 'bug', new UpdateLabelParams(color: 'fff'));
    }

    public function testDeleteLabelSuccess(): void
    {
        [$httpClient, $requestFactory, $request] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(204);
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $client->deleteLabel($repo, 'bug');

        $this->addToAssertionCount(1);
    }

    public function testDeleteLabelThrowsOnNon204(): void
    {
        [$httpClient, $requestFactory, $request] = $this->makeReadMocks();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');
        $httpClient->method('sendRequest')->willReturn($response);

        $config = new GithubApiConfig(accessToken: 'tok');
        $client = new GithubApiClient($httpClient, $requestFactory, $config);
        $repo = GithubRepository::fromFullName('owner/repo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('404 Not Found');

        $client->deleteLabel($repo, 'missing');
    }
}
