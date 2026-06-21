<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Factory for creating requests to delete an organization-level issue type
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
class DeleteIssueTypeRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private readonly GithubOrganizationId $org,
        private readonly int $issueTypeId
    ) {}

    public function create(): RequestInterface
    {
        $url = sprintf(
            'https://api.github.com/orgs/%s/issue-types/%d',
            (string) $this->org,
            $this->issueTypeId
        );

        $request = $this->requestFactory->createRequest('DELETE', $url);
        if ($this->config->accessToken !== '') {
            $request = $request->withHeader('Authorization', 'token ' . $this->config->accessToken);
        }
        $request = $request->withHeader('Accept', 'application/vnd.github.v3+json');

        return $request;
    }
}
