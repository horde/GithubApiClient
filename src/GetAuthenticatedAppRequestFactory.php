<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Factory for creating requests to get the authenticated GitHub App
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
class GetAuthenticatedAppRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config
    ) {}

    /**
     * Create HTTP request to get authenticated app
     *
     * @return RequestInterface
     */
    public function create(): RequestInterface
    {
        $url = sprintf('%s/app', $this->config->endpoint);

        $request = $this->requestFactory->createRequest('GET', $url);
        $request = $request->withHeader('Authorization', 'Bearer ' . $this->config->jwt);
        $request = $request->withHeader('Accept', 'application/vnd.github+json');
        $request = $request->withHeader('X-GitHub-Api-Version', $this->config->apiVersion);

        return $request;
    }
}
