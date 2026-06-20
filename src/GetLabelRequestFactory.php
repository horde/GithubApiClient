<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Factory for creating requests to get a single repository label
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
class GetLabelRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private readonly GithubRepository $repo,
        private readonly string $labelName
    ) {}

    public function create(): RequestInterface
    {
        // Label names can contain spaces, slashes, etc. — url-encode the segment.
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/labels/%s',
            $this->repo->owner,
            $this->repo->name,
            rawurlencode($this->labelName)
        );

        $request = $this->requestFactory->createRequest('GET', $url);
        if ($this->config->accessToken !== '') {
            $request = $request->withHeader('Authorization', 'token ' . $this->config->accessToken);
        }
        $request = $request->withHeader('Accept', 'application/vnd.github.v3+json');

        return $request;
    }
}
