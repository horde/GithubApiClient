<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Factory for creating rate limit check requests
 */
class RateLimitRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config
    ) {}

    /**
     * Create a request to check rate limit status
     *
     * @return RequestInterface
     */
    public function create(): RequestInterface
    {
        $uri = sprintf('%s/rate_limit', $this->config->endpoint);
        
        return $this->requestFactory->createRequest('GET', $uri)
            ->withHeader('Accept', 'application/vnd.github+json')
            ->withHeader('Authorization', 'Bearer ' . $this->config->accessToken)
            ->withHeader('X-GitHub-Api-Version', $this->config->apiVersion);
    }
}
