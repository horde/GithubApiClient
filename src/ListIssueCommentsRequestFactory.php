<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Factory for creating requests to list issue comments.
 *
 * Two modes:
 *
 *  - Repository-wide: every comment across every issue in the repo,
 *    optionally filtered by `$since` (ISO 8601 timestamp). Backed by
 *    `GET /repos/{owner}/{repo}/issues/comments`.
 *
 *  - Single-issue: comments on one issue by number, backed by
 *    `GET /repos/{owner}/{repo}/issues/{number}/comments`.
 *
 * Issue comments are distinct from pull-request review comments (which
 * carry `path` / `line` fields and live at a different endpoint). Note
 * that on GitHub, PR conversation comments — the ones without a code
 * anchor — arrive here rather than at the review-comments endpoint,
 * because PRs share the issue number space.
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
class ListIssueCommentsRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private int $itemsPerPage = 30,
        private readonly int $page = 1,
        private readonly string $since = '',
        private readonly string $sort = 'created',
        private readonly string $direction = 'asc'
    ) {}

    public function withItemsPerPage(int $items): self
    {
        $this->itemsPerPage = $items;
        return $this;
    }

    public function create(GithubRepository $repo, ?int $issueNumber = null): RequestInterface
    {
        $base = $issueNumber === null
            ? sprintf('%s/repos/%s/issues/comments', $this->config->endpoint, $repo->getFullName())
            : sprintf('%s/repos/%s/issues/%d/comments', $this->config->endpoint, $repo->getFullName(), $issueNumber);

        $query = [
            'per_page' => (string) $this->itemsPerPage,
            'page' => (string) $this->page,
            'sort' => $this->sort,
            'direction' => $this->direction,
        ];
        if ($this->since !== '') {
            $query['since'] = $this->since;
        }
        $uri = $base . '?' . http_build_query($query);

        $request = $this->requestFactory->createRequest('GET', $uri)
            ->withHeader('Accept', 'application/vnd.github+json')
            ->withHeader('X-GitHub-Api-Version', '2022-11-28');
        if ($this->config->accessToken !== '') {
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->config->accessToken);
        }
        return $request;
    }
}
