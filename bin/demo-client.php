<?php
/**
 * A demo client for the github client library
 */
declare(strict_types=1);
namespace Horde\GithubApiClient;

// Development Reference cli
require_once '../vendor/autoload.php';


use Horde\Injector\Injector;
use Horde\Injector\TopLevel;
use Horde\Http\Client\Options;
use Horde\Http\Client\Curl as CurlClient;
use Horde\Http\StreamFactory;
use Horde\Http\RequestFactory;
use Horde\Http\ResponseFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

// Bootstrap the injector
$strGithubApiToken = (string) getenv('GITHUB_TOKEN');
$injector = new Injector(new TopLevel());
// Setup a curl client. This is a demo, don't get too involved
$injector->setInstance(ClientInterface::class, new CurlClient(new ResponseFactory(), new StreamFactory(), new Options()));
$injector->setInstance(RequestFactoryInterface::class, new RequestFactory());
$injector->setInstance(GithubApiConfig::class, new GithubApiConfig(accessToken: $strGithubApiToken));
$client = $injector->get(GithubApiClient::class);
$repos = $client->listRepositoriesInOrganization(new GithubOrganizationId('horde'));
print_r($repos);

// List Releases of a repo
