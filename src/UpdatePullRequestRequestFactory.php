<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Factory for creating requests to update a pull request
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class UpdatePullRequestRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly GithubApiConfig $config,
        private readonly GithubRepository $repo,
        private readonly int $pullNumber,
        private readonly PullRequestUpdate $update
    ) {}

    /**
     * Create HTTP request to update a pull request
     *
     * @return RequestInterface
     */
    public function create(): RequestInterface
    {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/pulls/%d',
            $this->repo->owner,
            $this->repo->name,
            $this->pullNumber
        );

        $jsonBody = json_encode($this->update->toArray(), JSON_THROW_ON_ERROR);
        $stream = $this->streamFactory->createStream($jsonBody);

        $request = $this->requestFactory->createRequest('PATCH', $url);
        $request = $request->withHeader('Authorization', 'token ' . $this->config->accessToken);
        $request = $request->withHeader('Accept', 'application/vnd.github.v3+json');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody($stream);

        return $request;
    }
}
