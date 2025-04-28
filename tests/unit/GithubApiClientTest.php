<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use PHPUnit\Framework\TestCase;
use Horde\Http\Client\Options;
use Horde\Http\Client\Curl as CurlClient;
use Horde\Http\StreamFactory;
use Horde\Http\RequestFactory;
use Horde\Http\ResponseFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * @coversNothing
 */
final class GithubApiClientTest extends TestCase
{
    public function testCurlClientIsA()
    {
        $strGithubApiToken = 'ght_fooGarbage';
        $httpClient = new CurlClient(new ResponseFactory(), new StreamFactory(), new Options());
        $requestFactory = new RequestFactory();
        $config = new GithubApiConfig(accessToken: $strGithubApiToken);
        $apiClient = new GithubApiClient($httpClient, $requestFactory, $config);
        $this->assertInstanceOf(GithubApiClient::class, $apiClient);

    }
}
