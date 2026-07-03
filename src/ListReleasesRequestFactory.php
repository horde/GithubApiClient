<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Factory for creating requests to list releases in a repository
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class ListReleasesRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private int $itemsPerPage = 30,
        private readonly int $page = 1
    ) {}

    public function withItemsPerPage(int $items): self
    {
        $this->itemsPerPage = $items;
        return $this;
    }

    public function create(GithubRepository $repo): RequestInterface
    {
        $uri = sprintf(
            '%s/repos/%s/releases?per_page=%d&page=%d',
            $this->config->endpoint,
            $repo->getFullName(),
            $this->itemsPerPage,
            $this->page
        );
        $request = $this->requestFactory->createRequest('GET', $uri)
            ->withHeader('Accept', 'application/vnd.github+json')
            ->withHeader('X-GitHub-Api-Version', '2022-11-28');
        if ($this->config->accessToken !== '') {
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->config->accessToken);
        }
        return $request;
    }
}
