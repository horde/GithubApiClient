#!/usr/bin/env php
<?php
/**
 * A demo client for the github client library
 */
declare(strict_types=1);

namespace Horde\GithubApiClient;

// Development Reference cli
require_once dirname(__DIR__) . '/vendor/autoload.php';


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
if (empty($strGithubApiToken)) {
    echo "\nNo can do. Did you forget to export GITHUB_TOKEN variable?\n";
    exit;
}
$injector = new Injector(new TopLevel());
// Setup a curl client. This is a demo, don't get too involved
$injector->setInstance(ClientInterface::class, new CurlClient(new ResponseFactory(), new StreamFactory(), new Options()));
$injector->setInstance(RequestFactoryInterface::class, new RequestFactory());
$injector->setInstance(GithubApiConfig::class, new GithubApiConfig(accessToken: $strGithubApiToken));
$client = $injector->get(GithubApiClient::class);
$repos = $client->listRepositoriesInOrganization(new GithubOrganizationId('horde'));
foreach ($repos as $repo) {
    echo $repo->getFullName() . "\n";
}
// List Releases of a repo
