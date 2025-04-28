<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class ListPullRequestsRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private int $itemsPerPage = 50,
        private readonly string $sortDirection = 'asc',
        private readonly string $sortBy = 'created',
        private readonly int $page = 1,
        private string $headRef = '',
        private string $baseBranch = '',
        // TODO: Default filters, pagination, options
    ) {
    }

    public function withItemsPerPage(int $items): self
    {
        $this->itemsPerPage = $items;
        return $this;
    }
    public function withHeadRef(GithubOrganizationId $org, string $branch): self
    {
        $this->headRef = $org->getId() . ':' . $branch;
        return $this;
    }
    public function withBaseBranch(string $branch): self
    {
        $this->baseBranch = $branch;
        return $this;
    }

    public function create(GithubRepository $repo, string $baseBranch = '', string $headRef = '', string $state = 'open'): RequestInterface
    {
        $uri = sprintf(
            '%s/repos/%s/pulls?sort=%s&direction=%s&per_page=%d&page=%d',
            $this->config->endpoint,
            $repo->getFullName(),
            $this->sortBy,
            $this->sortDirection,
            $this->itemsPerPage,
            $this->page
        );
        if ($this->headRef && empty($headRef)) {
            $headRef = $this->headRef;
        }
        if ($headRef) {
            $uri .= sprintf('&head=%s', $headRef);
        }
        if ($this->baseBranch && empty($baseBranch)) {
            $baseBranch = $this->baseBranch;
        }
        if ($baseBranch) {
            $uri .= sprintf('&base=%s', $baseBranch);
        }
        if ($state) {
            $uri .= sprintf('&state=%s', $state);
        }
        // TODO: URI Helper might be more elegant here
        $request = $this->requestFactory->createRequest('GET', $uri)
        ->withHeader('Accept', 'application/vnd.github+json')
        ->withHeader('Authorization', 'Bearer ' . $this->config->accessToken)
        // TODO: Extract this
        ->withHeader('X-GitHub-Api-Version', '2022-11-28');
        return $request;
    }
}
