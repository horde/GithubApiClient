<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Builds a GET /repos/{owner}/{repo} request to read repository settings
 */
class GetRepositoryRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private readonly GithubRepository $repo
    ) {}

    public function create(): RequestInterface
    {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s',
            $this->repo->owner,
            $this->repo->name
        );

        $request = $this->requestFactory->createRequest('GET', $url);
        if ($this->config->accessToken !== '') {
            $request = $request->withHeader('Authorization', 'token ' . $this->config->accessToken);
        }
        $request = $request->withHeader('Accept', 'application/vnd.github.v3+json');

        return $request;
    }
}
