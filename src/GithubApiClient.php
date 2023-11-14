<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Horde\Http\RequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Exception;

class GithubApiClient
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config
    ) {
    }

    public function listRepositoriesInOrganization(GithubOrganizationId $org): GithubRepositoryList
    {
        $requestFactory = new ListRepositoriesInOrganizationRequestFactory($this->requestFactory, $this->config, $org);
        $request = $requestFactory->create();
        $repos = [];
        while (true) {
            $response = $this->httpClient->sendRequest($request);
            if ($response->getReasonPhrase() == 'OK') {
                // Process response content
                $repos = array_merge($repos, json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR));
                $pagination = new GithubApiPagination($request, $response);
                if (!$pagination->hasNextLink()) {
                    break;
                }
                $request = $pagination->nextRequest();
            } else {
                throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
                break;
            }
        }
        return new GithubRepositoryList($repos);
    }
}
