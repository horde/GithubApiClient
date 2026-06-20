<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Factory for creating requests to update a PR review comment.
 *
 * Note URL asymmetry: unlike create/list which include the PR number,
 * update/delete address review comments by id alone.
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
class UpdateReviewCommentRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly GithubApiConfig $config,
        private readonly GithubRepository $repo,
        private readonly int $commentId,
        private readonly string $body
    ) {}

    public function create(): RequestInterface
    {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/pulls/comments/%d',
            $this->repo->owner,
            $this->repo->name,
            $this->commentId
        );

        $jsonBody = json_encode(['body' => $this->body], JSON_THROW_ON_ERROR);
        $stream = $this->streamFactory->createStream($jsonBody);

        $request = $this->requestFactory->createRequest('PATCH', $url);
        if ($this->config->accessToken !== '') {
            $request = $request->withHeader('Authorization', 'token ' . $this->config->accessToken);
        }
        $request = $request->withHeader('Accept', 'application/vnd.github.v3+json');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody($stream);

        return $request;
    }
}
