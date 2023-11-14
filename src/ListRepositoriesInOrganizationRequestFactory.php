<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class ListRepositoriesInOrganizationRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private GithubOrganizationId $defaultOrg,
        private int $itemsPerPage = 50,
        private readonly string $sortDirection = 'asc',
        private readonly string $sortBy = 'full_name',
        private readonly int $page = 1
        // TODO: Default filters, pagination, options
    ) {

    }

    public function withItemsPerPage(int $items): self
    {
        $this->itemsPerPage = $items;
        return $this;
    }

    public function create(GithubOrganizationId $org = null): RequestInterface
    {
        if (empty($org)) {
            $org = $this->defaultOrg;
        }
        $uri =         sprintf(
            '%s/orgs/%s/repos?sort=%s&direction=%s&per_page=%d&page=%d',
            $this->config->endpoint,
            (string) $org,
            $this->sortBy,
            $this->sortDirection,
            $this->itemsPerPage,
            $this->page
        );
        // TODO: URI Helper might be more elegant here
        $request = $this->requestFactory->createRequest('GET', $uri)
        ->withHeader('Accept', 'application/vnd.github+json')
        ->withHeader('Authorization', 'Bearer ' . $this->config->accessToken)
        // TODO: Extract this
        ->withHeader('X-GitHub-Api-Version', '2022-11-28');
        return $request;
    }
}
