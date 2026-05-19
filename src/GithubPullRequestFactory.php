<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use stdClass;
use InvalidArgumentException;

/**
 * Factory for creating GithubPullRequest objects from API responses
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
class GithubPullRequestFactory
{
    private GithubUserFactory $userFactory;
    private GithubLabelFactory $labelFactory;

    public function __construct()
    {
        $this->userFactory = new GithubUserFactory();
        $this->labelFactory = new GithubLabelFactory();
    }

    public function createFromApiResponse(stdClass $apiResponse): GithubPullRequest
    {
        // Parse labels
        $labels = [];
        if (isset($apiResponse->labels) && is_array($apiResponse->labels)) {
            foreach ($apiResponse->labels as $labelData) {
                $labels[] = $this->labelFactory->createFromApiResponse($labelData);
            }
        }

        // Parse requested reviewers
        $requestedReviewers = [];
        if (isset($apiResponse->requested_reviewers) && is_array($apiResponse->requested_reviewers)) {
            foreach ($apiResponse->requested_reviewers as $reviewerData) {
                $requestedReviewers[] = $this->userFactory->createFromApiResponse($reviewerData);
            }
        }

        return new GithubPullRequest(
            number: $apiResponse->number,
            title: $apiResponse->title,
            body: $apiResponse->body ?? '',
            htmlUrl: $apiResponse->html_url,
            apiUrl: $apiResponse->url,
            state: $apiResponse->state,
            draft: $apiResponse->draft ?? false,
            merged: $apiResponse->merged ?? false,
            mergedAt: $apiResponse->merged_at ?? null,
            createdAt: $apiResponse->created_at ?? '',
            updatedAt: $apiResponse->updated_at ?? '',
            baseRepo: GithubRepository::fromApiArray((array) $apiResponse->base->repo),
            headRepo: GithubRepository::fromApiArray((array) $apiResponse->head->repo),
            baseBranch: $apiResponse->base->ref ?? '',
            headBranch: $apiResponse->head->ref ?? '',
            author: $this->userFactory->createFromApiResponse($apiResponse->user),
            labels: $labels,
            requestedReviewers: $requestedReviewers,
            mergeableState: $apiResponse->mergeable_state ?? null,
            mergeable: $apiResponse->mergeable ?? null,
            nodeId: $apiResponse->node_id ?? '',
        );
    }
}
