<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Factory for creating requests to list pull request comments
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
class ListPullRequestCommentsRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private readonly GithubRepository $repo,
        private readonly int $pullNumber
    ) {}

    /**
     * Create HTTP request to list pull request comments
     *
     * @return RequestInterface
     */
    public function create(): RequestInterface
    {
        // Note: GitHub uses the issues endpoint for PR comments
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/issues/%d/comments',
            $this->repo->owner,
            $this->repo->name,
            $this->pullNumber
        );

        $request = $this->requestFactory->createRequest('GET', $url);
        $request = $request->withHeader('Authorization', 'token ' . $this->config->accessToken);
        $request = $request->withHeader('Accept', 'application/vnd.github.v3+json');

        return $request;
    }
}
