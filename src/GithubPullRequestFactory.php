<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use stdClass;
use InvalidArgumentException;

class GithubPullRequestFactory
{
    public function createFromApiResponse(stdClass $apiResponse): GithubPullRequest
    {
        return new GithubPullRequest(
            number: $apiResponse->number,
            title: $apiResponse->title,
            htmlUrl: $apiResponse->html_url,
            apiUrl: $apiResponse->url,
            state: $apiResponse->state,
            baseRepo: GithubRepository::fromApiArray((array)$apiResponse->base->repo),
            headRepo: GithubRepository::fromApiArray((array)$apiResponse->head->repo),
        );
    }
}
