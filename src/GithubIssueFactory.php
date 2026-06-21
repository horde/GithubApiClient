<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Factory for creating GithubIssue objects from API responses
 *
 * Decoding here orchestrates the nested value objects (User, Label, Milestone,
 * Type) via their own factories; missing fields fall back to null/empty cleanly.
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
class GithubIssueFactory
{
    /**
     * Create GithubIssue from GitHub API response
     *
     * @param object $data Decoded JSON issue object from API
     * @return GithubIssue
     */
    public function createFromApiResponse(object $data): GithubIssue
    {
        $userFactory = new GithubUserFactory();
        $labelFactory = new GithubLabelFactory();

        $author = isset($data->user) && is_object($data->user)
            ? $userFactory->createFromApiResponse($data->user)
            : new GithubUser(login: '', id: 0, avatarUrl: '', htmlUrl: '');

        $labels = [];
        if (isset($data->labels) && is_array($data->labels)) {
            foreach ($data->labels as $labelData) {
                if (is_object($labelData)) {
                    $labels[] = $labelFactory->createFromApiResponse($labelData);
                }
            }
        }

        $assignees = [];
        if (isset($data->assignees) && is_array($data->assignees)) {
            foreach ($data->assignees as $userData) {
                if (is_object($userData)) {
                    $assignees[] = $userFactory->createFromApiResponse($userData);
                }
            }
        }

        $milestone = null;
        if (isset($data->milestone) && is_object($data->milestone)) {
            $milestone = GithubMilestone::fromApiResponse($data->milestone);
        }

        $type = null;
        if (isset($data->type) && is_object($data->type)) {
            $type = GithubIssueType::fromApiResponse($data->type);
        }

        $fieldValues = $this->parseFieldValues($data);

        return new GithubIssue(
            id: $data->id ?? 0,
            number: $data->number ?? 0,
            title: $data->title ?? '',
            body: $data->body ?? '',
            state: $data->state ?? 'open',
            stateReason: $data->state_reason ?? null,
            htmlUrl: $data->html_url ?? '',
            apiUrl: $data->url ?? '',
            author: $author,
            labels: $labels,
            assignees: $assignees,
            milestone: $milestone,
            type: $type,
            comments: $data->comments ?? 0,
            createdAt: $data->created_at ?? '',
            updatedAt: $data->updated_at ?? '',
            closedAt: $data->closed_at ?? null,
            isPullRequest: isset($data->pull_request),
            fieldValues: $fieldValues,
            nodeId: $data->node_id ?? '',
        );
    }

    /**
     * Parse the issue_field_values array into a name → value map.
     *
     * GitHub returns each entry as an object carrying a name and a value;
     * we project the array into a flat map so callers don't have to walk
     * nested objects. Write-side support for these values requires the
     * GraphQL surface and is not in scope for this iteration.
     *
     * @param object $data Decoded JSON issue object
     * @return array<string, mixed>
     */
    private function parseFieldValues(object $data): array
    {
        if (!isset($data->issue_field_values) || !is_array($data->issue_field_values)) {
            return [];
        }

        $values = [];
        foreach ($data->issue_field_values as $entry) {
            if (!is_object($entry)) {
                continue;
            }
            $name = $entry->name ?? '';
            if ($name === '') {
                continue;
            }
            $values[$name] = $entry->value ?? null;
        }

        return $values;
    }
}
