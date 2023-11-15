<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Horde\Http\RequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Exception;
use Stringable;
use OutOfBoundsException;

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
                $repos = $this->parseJsonAndMerge($repos, (string) $response->getBody());
                $pagination = new GithubApiPagination($request, $response);
                if (!$pagination->hasNextLink()) {
                    break;
                }
                $request = $pagination->nextRequest();
            } else {
                throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
            }
        }

        return new GithubRepositoryList($repos);
    }

    /**
     * @param array<mixed> $repos
     * @param string $json
     *  
     * @return array<array<string|Stringable|int|null>>
     **/
	private function parseJsonAndMerge(array $repos, string $json): array
    {
        $decoded = (array) json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        foreach ($decoded as $repoArray) {
            if (is_array($repoArray)) {
                $repos[] = GithubRepository::isValidArrayRepresentation($repoArray) ? $repoArray : throw new OutOfBoundsException('Element does not contain correct structure');
            } else {
                throw new OutOfBoundsException('List does contain non-list element');
            }
        }
        return $repos;
    }
}
