<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Factory for creating authenticated user requests
 */
class AuthenticatedUserRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config
    ) {}

    /**
     * Create a request to get authenticated user information
     *
     * @return RequestInterface
     */
    public function create(): RequestInterface
    {
        $uri = sprintf('%s/user', $this->config->endpoint);
        
        return $this->requestFactory->createRequest('GET', $uri)
            ->withHeader('Accept', 'application/vnd.github+json')
            ->withHeader('Authorization', 'Bearer ' . $this->config->accessToken)
            ->withHeader('X-GitHub-Api-Version', $this->config->apiVersion);
    }
}
