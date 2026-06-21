<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Factory for creating requests to list issues in a repository
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
class ListIssuesRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private readonly GithubRepository $repo,
        private readonly string $state = 'open',
        private readonly string $labels = '',
        private readonly string $milestone = '',
        private readonly string $assignee = ''
    ) {}

    public function create(): RequestInterface
    {
        $query = ['state' => $this->state];
        if ($this->labels !== '') {
            $query['labels'] = $this->labels;
        }
        if ($this->milestone !== '') {
            $query['milestone'] = $this->milestone;
        }
        if ($this->assignee !== '') {
            $query['assignee'] = $this->assignee;
        }

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/issues?%s',
            $this->repo->owner,
            $this->repo->name,
            http_build_query($query)
        );

        $request = $this->requestFactory->createRequest('GET', $url);
        if ($this->config->accessToken !== '') {
            $request = $request->withHeader('Authorization', 'token ' . $this->config->accessToken);
        }
        $request = $request->withHeader('Accept', 'application/vnd.github.v3+json');

        return $request;
    }
}
