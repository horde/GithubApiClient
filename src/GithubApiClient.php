<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Horde\Http\RequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Exception;
use Stringable;
use OutOfBoundsException;

class GithubApiClient
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private readonly ?StreamFactoryInterface $streamFactory = null
    ) {}

    public static function withAuthenticatedClient(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        ?StreamFactoryInterface $streamFactory = null,
        string $endpoint = 'https://api.github.com',
        string $apiVersion = '2022-11-28',
    ): self {
        return new self(
            $httpClient,
            $requestFactory,
            new GithubApiConfig(
                endpoint: $endpoint,
                apiVersion: $apiVersion,
            ),
            $streamFactory,
        );
    }

    public function listRepositoriesInOrganization(GithubOrganizationId $org): GithubRepositoryList
    {
        $requestFactory = new ListRepositoriesInOrganizationRequestFactory($this->requestFactory, $this->config, $org);
        $request = $requestFactory->create();
        $repos = [];
        while (true) {
            $response = $this->httpClient->sendRequest($request);
            if ($response->getReasonPhrase() == 'OK') {
                $repos = $this->parseJsonAndMerge($repos, (string) $response->getBody());
                $pagination = new GithubApiPagination($request, $response);
                if (!$pagination->hasNextLink()) {
                    break;
                }
                $request = $pagination->nextRequest();
            } else {
                throw new Exception($this->parseErrorResponse($response));
            }
        }

        return new GithubRepositoryList($repos);
    }
    public function listPullRequests(GithubRepository $repo, string $baseBranch = '', string $headRef = '', string $state = 'open'): GithubPullRequestList
    {
        $pullRequests = [];

        $requestFactory = new ListPullRequestsRequestFactory($this->requestFactory, $this->config);
        $request = $requestFactory->create($repo, $baseBranch, $headRef);
        // TODO: Pagination
        $response = $this->httpClient->sendRequest($request);
        if ($response->getReasonPhrase() == 'OK') {
            $pullRequestData = json_decode((string) $response->getBody());
            $prFactory = new GithubPullRequestFactory();
            foreach ($pullRequestData as $pr) {
                $pullRequests[] = $prFactory->createFromApiResponse($pr);
            }
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
        return new GithubPullRequestList($pullRequests);
    }

    /**
     * Get current rate limit status for the authenticated user
     *
     * @return RateLimit
     * @throws Exception
     */
    public function getRateLimit(): RateLimit
    {
        $requestFactory = new RateLimitRequestFactory($this->requestFactory, $this->config);
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getReasonPhrase() == 'OK') {
            $data = json_decode((string) $response->getBody());
            return RateLimit::fromApiResponse($data);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get OAuth scopes/permissions for the current access token
     *
     * @return TokenScopes
     * @throws Exception
     */
    public function getTokenScopes(): TokenScopes
    {
        $requestFactory = new AuthenticatedUserRequestFactory($this->requestFactory, $this->config);
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getReasonPhrase() == 'OK') {
            // Parse X-OAuth-Scopes header
            $scopesHeader = $response->getHeader('X-OAuth-Scopes');
            $scopesValue = !empty($scopesHeader) ? $scopesHeader[0] : '';
            return TokenScopes::fromHeader($scopesValue);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get a single pull request with complete details
     *
     * @param GithubRepository $repo The repository
     * @param int $number The pull request number
     * @return GithubPullRequest
     * @throws Exception
     */
    public function getPullRequest(GithubRepository $repo, int $number): GithubPullRequest
    {
        $requestFactory = new GetPullRequestRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $number
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $prFactory = new GithubPullRequestFactory();
            return $prFactory->createFromApiResponse($data);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Update a pull request (title, body, base, or state)
     *
     * @param GithubRepository $repo The repository
     * @param int $number The pull request number
     * @param PullRequestUpdate $update The fields to update
     * @return GithubPullRequest The updated pull request
     * @throws Exception
     */
    public function updatePullRequest(GithubRepository $repo, int $number, PullRequestUpdate $update): GithubPullRequest
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for updatePullRequest. Please provide it in the constructor.');
        }

        $requestFactory = new UpdatePullRequestRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $number,
            $update
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $prFactory = new GithubPullRequestFactory();
            return $prFactory->createFromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * List issues in a repository
     *
     * Returns both regular issues and pull requests (every PR is also an issue
     * at the API level). Use `GithubIssue::$isPullRequest` to distinguish them.
     *
     * @param GithubRepository $repo The repository
     * @param string $state 'open' (default), 'closed', or 'all'
     * @param string $labels Comma-separated label names to filter by; empty for no filter
     * @param string $milestone Milestone number, '*' (any), or 'none'; empty for no filter
     * @param string $assignee Assignee login, '*' (any), or 'none'; empty for no filter
     * @return GithubIssueList
     * @throws Exception
     */
    public function listIssues(
        GithubRepository $repo,
        string $state = 'open',
        string $labels = '',
        string $milestone = '',
        string $assignee = ''
    ): GithubIssueList {
        $requestFactory = new ListIssuesRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $state,
            $labels,
            $milestone,
            $assignee
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $issueFactory = new GithubIssueFactory();
            $issues = [];
            if (is_array($data)) {
                foreach ($data as $issueData) {
                    if (is_object($issueData)) {
                        $issues[] = $issueFactory->createFromApiResponse($issueData);
                    }
                }
            }
            return new GithubIssueList($issues);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get a single issue
     *
     * Works for both regular issues and pull requests — issues and PRs share
     * a single counter per repo, so this returns whatever lives at #$number.
     * Inspect `GithubIssue::$isPullRequest` to detect a PR.
     *
     * @param GithubRepository $repo The repository
     * @param int $number The issue (or PR) number
     * @return GithubIssue
     * @throws Exception
     */
    public function getIssue(GithubRepository $repo, int $number): GithubIssue
    {
        $requestFactory = new GetIssueRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $number
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $issueFactory = new GithubIssueFactory();
            return $issueFactory->createFromApiResponse($data);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Create a new issue
     *
     * @param GithubRepository $repo The repository
     * @param CreateIssueParams $params The issue parameters
     * @return GithubIssue The created issue
     * @throws Exception
     */
    public function createIssue(GithubRepository $repo, CreateIssueParams $params): GithubIssue
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createIssue. Please provide it in the constructor.');
        }

        $requestFactory = new CreateIssueRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            $issueFactory = new GithubIssueFactory();
            return $issueFactory->createFromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Update an issue
     *
     * The IssueUpdate DTO distinguishes "leave alone" from "clear" — calling
     * withMilestone(null) or withType(null) emits literal null in the request
     * body, which is GitHub's signal to clear the current assignment.
     *
     * @param GithubRepository $repo The repository
     * @param int $number The issue (or PR) number
     * @param IssueUpdate $update The update DTO
     * @return GithubIssue The updated issue
     * @throws Exception
     */
    public function updateIssue(GithubRepository $repo, int $number, IssueUpdate $update): GithubIssue
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for updateIssue. Please provide it in the constructor.');
        }

        $requestFactory = new UpdateIssueRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $number,
            $update
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $issueFactory = new GithubIssueFactory();
            return $issueFactory->createFromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Close an issue (symmetric with closePullRequest)
     *
     * @param GithubRepository $repo The repository
     * @param int $number The issue number
     * @return GithubIssue The closed issue
     * @throws Exception
     */
    public function closeIssue(GithubRepository $repo, int $number): GithubIssue
    {
        return $this->updateIssue($repo, $number, (new IssueUpdate())->withState('closed'));
    }

    /**
     * Reopen a closed issue (symmetric with reopenPullRequest)
     *
     * @param GithubRepository $repo The repository
     * @param int $number The issue number
     * @return GithubIssue The reopened issue
     * @throws Exception
     */
    public function reopenIssue(GithubRepository $repo, int $number): GithubIssue
    {
        return $this->updateIssue($repo, $number, (new IssueUpdate())->withState('open'));
    }

    /**
     * List milestones in a repository
     *
     * @param GithubRepository $repo The repository
     * @param string $state 'open' (default), 'closed', or 'all'
     * @return GithubMilestoneList
     * @throws Exception
     */
    public function listMilestones(GithubRepository $repo, string $state = 'open'): GithubMilestoneList
    {
        $requestFactory = new ListMilestonesRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $state
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $milestones = [];
            if (is_array($data)) {
                foreach ($data as $entry) {
                    if (is_object($entry)) {
                        $milestones[] = GithubMilestone::fromApiResponse($entry);
                    }
                }
            }
            return new GithubMilestoneList($milestones);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get a single milestone
     *
     * @param GithubRepository $repo The repository
     * @param int $milestoneNumber The milestone number
     * @return GithubMilestone
     * @throws Exception
     */
    public function getMilestone(GithubRepository $repo, int $milestoneNumber): GithubMilestone
    {
        $requestFactory = new GetMilestoneRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $milestoneNumber
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubMilestone::fromApiResponse($data);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Create a milestone
     *
     * @param GithubRepository $repo The repository
     * @param CreateMilestoneParams $params Milestone parameters
     * @return GithubMilestone The created milestone
     * @throws Exception
     */
    public function createMilestone(GithubRepository $repo, CreateMilestoneParams $params): GithubMilestone
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createMilestone. Please provide it in the constructor.');
        }

        $requestFactory = new CreateMilestoneRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            return GithubMilestone::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Update a milestone
     *
     * @param GithubRepository $repo The repository
     * @param int $milestoneNumber The milestone number
     * @param UpdateMilestoneParams $params Update parameters
     * @return GithubMilestone The updated milestone
     * @throws Exception
     */
    public function updateMilestone(GithubRepository $repo, int $milestoneNumber, UpdateMilestoneParams $params): GithubMilestone
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for updateMilestone. Please provide it in the constructor.');
        }

        $requestFactory = new UpdateMilestoneRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $milestoneNumber,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubMilestone::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Delete a milestone
     *
     * @param GithubRepository $repo The repository
     * @param int $milestoneNumber The milestone number
     * @return void
     * @throws Exception
     */
    public function deleteMilestone(GithubRepository $repo, int $milestoneNumber): void
    {
        $requestFactory = new DeleteMilestoneRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $milestoneNumber
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 204) {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Assign a milestone to an issue (convenience wrapper for updateIssue)
     *
     * @param GithubRepository $repo The repository
     * @param int $issueNumber The issue (or PR) number
     * @param int $milestoneNumber The milestone number to assign
     * @return GithubIssue The updated issue
     * @throws Exception
     */
    public function assignMilestone(GithubRepository $repo, int $issueNumber, int $milestoneNumber): GithubIssue
    {
        return $this->updateIssue($repo, $issueNumber, (new IssueUpdate())->withMilestone($milestoneNumber));
    }

    /**
     * Clear the milestone on an issue (convenience wrapper for updateIssue)
     *
     * @param GithubRepository $repo The repository
     * @param int $issueNumber The issue (or PR) number
     * @return GithubIssue The updated issue
     * @throws Exception
     */
    public function unassignMilestone(GithubRepository $repo, int $issueNumber): GithubIssue
    {
        return $this->updateIssue($repo, $issueNumber, (new IssueUpdate())->withMilestone(null));
    }

    /**
     * List organization-level issue types
     *
     * @param GithubOrganizationId $org The organization
     * @return GithubIssueTypeList
     * @throws Exception
     */
    public function listIssueTypes(GithubOrganizationId $org): GithubIssueTypeList
    {
        $requestFactory = new ListIssueTypesRequestFactory(
            $this->requestFactory,
            $this->config,
            $org
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $types = [];
            if (is_array($data)) {
                foreach ($data as $entry) {
                    if (is_object($entry)) {
                        $types[] = GithubIssueType::fromApiResponse($entry);
                    }
                }
            }
            return new GithubIssueTypeList($types);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Create an organization-level issue type
     *
     * @param GithubOrganizationId $org The organization
     * @param CreateIssueTypeParams $params The issue type parameters
     * @return GithubIssueType The created issue type
     * @throws Exception
     */
    public function createIssueType(GithubOrganizationId $org, CreateIssueTypeParams $params): GithubIssueType
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createIssueType. Please provide it in the constructor.');
        }

        $requestFactory = new CreateIssueTypeRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $org,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            return GithubIssueType::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Update an organization-level issue type
     *
     * GitHub uses PUT for this endpoint (not PATCH).
     *
     * @param GithubOrganizationId $org The organization
     * @param int $issueTypeId The numeric id of the issue type
     * @param UpdateIssueTypeParams $params Update parameters
     * @return GithubIssueType The updated issue type
     * @throws Exception
     */
    public function updateIssueType(GithubOrganizationId $org, int $issueTypeId, UpdateIssueTypeParams $params): GithubIssueType
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for updateIssueType. Please provide it in the constructor.');
        }

        $requestFactory = new UpdateIssueTypeRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $org,
            $issueTypeId,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubIssueType::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Delete an organization-level issue type
     *
     * @param GithubOrganizationId $org The organization
     * @param int $issueTypeId The numeric id of the issue type
     * @return void
     * @throws Exception
     */
    public function deleteIssueType(GithubOrganizationId $org, int $issueTypeId): void
    {
        $requestFactory = new DeleteIssueTypeRequestFactory(
            $this->requestFactory,
            $this->config,
            $org,
            $issueTypeId
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 204) {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Assign an issue type to an issue (convenience wrapper for updateIssue)
     *
     * Type assignment is keyed by type name, not id, on the issue PATCH endpoint.
     *
     * @param GithubRepository $repo The repository
     * @param int $issueNumber The issue (or PR) number
     * @param string $typeName The issue type name (e.g. "Bug")
     * @return GithubIssue The updated issue
     * @throws Exception
     */
    public function assignIssueType(GithubRepository $repo, int $issueNumber, string $typeName): GithubIssue
    {
        return $this->updateIssue($repo, $issueNumber, (new IssueUpdate())->withType($typeName));
    }

    /**
     * Clear the issue type on an issue (convenience wrapper for updateIssue)
     *
     * @param GithubRepository $repo The repository
     * @param int $issueNumber The issue (or PR) number
     * @return GithubIssue The updated issue
     * @throws Exception
     */
    public function unassignIssueType(GithubRepository $repo, int $issueNumber): GithubIssue
    {
        return $this->updateIssue($repo, $issueNumber, (new IssueUpdate())->withType(null));
    }

    /**
     * List all comments on a pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The pull request number
     * @return GithubCommentList
     * @throws Exception
     */
    public function listPullRequestComments(GithubRepository $repo, int $number): GithubCommentList
    {
        $requestFactory = new ListPullRequestCommentsRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $number
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $commentFactory = new GithubCommentFactory();
            $comments = [];
            foreach ($data as $commentData) {
                $comments[] = $commentFactory->createFromApiResponse($commentData);
            }
            return new GithubCommentList($comments);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Create a comment on a pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The pull request number
     * @param string $body The comment body
     * @return GithubComment The created comment
     * @throws Exception
     */
    public function createPullRequestComment(GithubRepository $repo, int $number, string $body): GithubComment
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createPullRequestComment. Please provide it in the constructor.');
        }

        $requestFactory = new CreatePullRequestCommentRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $number,
            $body
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            $commentFactory = new GithubCommentFactory();
            return $commentFactory->createFromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Update a comment
     *
     * @param GithubRepository $repo The repository
     * @param int $commentId The comment ID
     * @param string $body The new comment body
     * @return GithubComment The updated comment
     * @throws Exception
     */
    public function updateComment(GithubRepository $repo, int $commentId, string $body): GithubComment
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for updateComment. Please provide it in the constructor.');
        }

        $requestFactory = new UpdateCommentRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $commentId,
            $body
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $commentFactory = new GithubCommentFactory();
            return $commentFactory->createFromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Delete a comment
     *
     * @param GithubRepository $repo The repository
     * @param int $commentId The comment ID
     * @return void
     * @throws Exception
     */
    public function deleteComment(GithubRepository $repo, int $commentId): void
    {
        $requestFactory = new DeleteCommentRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $commentId
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 204) {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * List all reviews on a pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The pull request number
     * @return GithubReviewList
     * @throws Exception
     */
    public function listPullRequestReviews(GithubRepository $repo, int $number): GithubReviewList
    {
        $requestFactory = new ListPullRequestReviewsRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $number
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $reviewFactory = new GithubReviewFactory();
            $reviews = [];
            foreach ($data as $reviewData) {
                $reviews[] = $reviewFactory->createFromApiResponse($reviewData);
            }
            return new GithubReviewList($reviews);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Request reviewers for a pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The pull request number
     * @param array<string> $reviewers User logins to request as reviewers
     * @param array<string> $teamReviewers Team slugs to request as reviewers
     * @return GithubPullRequest The updated pull request
     * @throws Exception
     */
    public function requestReviewers(GithubRepository $repo, int $number, array $reviewers = [], array $teamReviewers = []): GithubPullRequest
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for requestReviewers. Please provide it in the constructor.');
        }

        $requestFactory = new RequestReviewersRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $number,
            $reviewers,
            $teamReviewers
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            $prFactory = new GithubPullRequestFactory();
            return $prFactory->createFromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Create a review for a pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The pull request number
     * @param CreateReviewParams $params The review parameters
     * @return GithubReview The created review
     * @throws Exception
     */
    public function createReview(GithubRepository $repo, int $number, CreateReviewParams $params): GithubReview
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createReview. Please provide it in the constructor.');
        }

        $requestFactory = new CreateReviewRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $number,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubReview::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Create a per-line review comment on a pull request
     *
     * Distinct from createPullRequestComment, which posts to the conversation
     * thread. Review comments anchor to a specific file path and diff line.
     *
     * @param GithubRepository $repo The repository
     * @param int $prNumber The pull request number
     * @param CreateReviewCommentParams $params The review-comment parameters
     * @return GithubReviewComment The created review comment
     * @throws Exception
     */
    public function createReviewComment(GithubRepository $repo, int $prNumber, CreateReviewCommentParams $params): GithubReviewComment
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createReviewComment. Please provide it in the constructor.');
        }

        $requestFactory = new CreateReviewCommentRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $prNumber,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            return GithubReviewComment::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * List per-line review comments on a pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $prNumber The pull request number
     * @return GithubReviewCommentList
     * @throws Exception
     */
    public function listReviewComments(GithubRepository $repo, int $prNumber): GithubReviewCommentList
    {
        $requestFactory = new ListReviewCommentsRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $prNumber
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $factory = new GithubReviewCommentFactory();
            $comments = [];
            foreach ($data as $item) {
                $comments[] = $factory->createFromApiResponse($item);
            }
            return new GithubReviewCommentList($comments);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Update a per-line review comment
     *
     * Note: addressed by comment id only (no PR number in the URL).
     *
     * @param GithubRepository $repo The repository
     * @param int $commentId The review-comment ID
     * @param string $body The new comment body
     * @return GithubReviewComment The updated review comment
     * @throws Exception
     */
    public function updateReviewComment(GithubRepository $repo, int $commentId, string $body): GithubReviewComment
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for updateReviewComment. Please provide it in the constructor.');
        }

        $requestFactory = new UpdateReviewCommentRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $commentId,
            $body
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubReviewComment::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Delete a per-line review comment
     *
     * Note: addressed by comment id only (no PR number in the URL).
     *
     * @param GithubRepository $repo The repository
     * @param int $commentId The review-comment ID
     * @return void
     * @throws Exception
     */
    public function deleteReviewComment(GithubRepository $repo, int $commentId): void
    {
        $requestFactory = new DeleteReviewCommentRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $commentId
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 204) {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get combined status for a commit
     *
     * @param GithubRepository $repo The repository
     * @param string $ref The commit SHA, branch name, or tag name
     * @return GithubCombinedStatus
     * @throws Exception
     */
    public function getCombinedStatus(GithubRepository $repo, string $ref): GithubCombinedStatus
    {
        $requestFactory = new GetCombinedStatusRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $ref
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubCombinedStatus::fromApiResponse($data);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * List check runs for a commit
     *
     * @param GithubRepository $repo The repository
     * @param string $ref The commit SHA, branch name, or tag name
     * @return GithubCheckRunList
     * @throws Exception
     */
    public function listCheckRuns(GithubRepository $repo, string $ref): GithubCheckRunList
    {
        $requestFactory = new ListCheckRunsRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $ref
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $checkRuns = [];
            if (isset($data->check_runs) && is_array($data->check_runs)) {
                foreach ($data->check_runs as $checkRunData) {
                    $checkRuns[] = GithubCheckRun::fromApiResponse($checkRunData);
                }
            }
            return new GithubCheckRunList($checkRuns);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Create a Check Run on a commit
     *
     * @param GithubRepository $repo The repository
     * @param CreateCheckRunParams $params The check-run parameters
     * @return GithubCheckRun The created check run
     * @throws Exception
     */
    public function createCheckRun(GithubRepository $repo, CreateCheckRunParams $params): GithubCheckRun
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createCheckRun. Please provide it in the constructor.');
        }

        $requestFactory = new CreateCheckRunRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            return GithubCheckRun::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Update an existing Check Run
     *
     * @param GithubRepository $repo The repository
     * @param int $checkRunId The check-run ID
     * @param UpdateCheckRunParams $params The update parameters
     * @return GithubCheckRun The updated check run
     * @throws Exception
     */
    public function updateCheckRun(GithubRepository $repo, int $checkRunId, UpdateCheckRunParams $params): GithubCheckRun
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for updateCheckRun. Please provide it in the constructor.');
        }

        $requestFactory = new UpdateCheckRunRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $checkRunId,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubCheckRun::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get a single Check Run by ID
     *
     * @param GithubRepository $repo The repository
     * @param int $checkRunId The check-run ID
     * @return GithubCheckRun
     * @throws Exception
     */
    public function getCheckRun(GithubRepository $repo, int $checkRunId): GithubCheckRun
    {
        $requestFactory = new GetCheckRunRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $checkRunId
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubCheckRun::fromApiResponse($data);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * List labels on an issue or pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The issue or pull request number
     * @return GithubLabelList
     * @throws Exception
     */
    public function listIssueLabels(GithubRepository $repo, int $number): GithubLabelList
    {
        $requestFactory = new ListIssueLabelsRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $number
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $labelFactory = new GithubLabelFactory();
            $labels = [];
            foreach ($data as $labelData) {
                $labels[] = $labelFactory->createFromApiResponse($labelData);
            }
            return new GithubLabelList($labels);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Add labels to an issue or pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The issue or pull request number
     * @param array<string> $labels Label names to add
     * @return GithubLabelList The updated list of labels
     * @throws Exception
     */
    public function addLabels(GithubRepository $repo, int $number, array $labels): GithubLabelList
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for addLabels. Please provide it in the constructor.');
        }

        $requestFactory = new AddLabelsRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $number,
            $labels
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $labelFactory = new GithubLabelFactory();
            $labelsList = [];
            foreach ($data as $labelData) {
                $labelsList[] = $labelFactory->createFromApiResponse($labelData);
            }
            return new GithubLabelList($labelsList);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Set (replace) all labels on an issue or pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The issue or pull request number
     * @param array<string> $labels Label names to set (replaces all existing labels)
     * @return GithubLabelList The updated list of labels
     * @throws Exception
     */
    public function setLabels(GithubRepository $repo, int $number, array $labels): GithubLabelList
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for setLabels. Please provide it in the constructor.');
        }

        $requestFactory = new SetLabelsRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $number,
            $labels
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $labelFactory = new GithubLabelFactory();
            $labelsList = [];
            foreach ($data as $labelData) {
                $labelsList[] = $labelFactory->createFromApiResponse($labelData);
            }
            return new GithubLabelList($labelsList);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Remove a label from an issue or pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The issue or pull request number
     * @param string $labelName The name of the label to remove
     * @return void
     * @throws Exception
     */
    public function removeLabel(GithubRepository $repo, int $number, string $labelName): void
    {
        $requestFactory = new RemoveLabelRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $number,
            $labelName
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Remove all labels from an issue or pull request
     *
     * Symmetric with removeLabel(...) but clears every label assignment at once.
     * Useful for CI flows that reset state ("clear all lane-outcome labels,
     * then add the new ones").
     *
     * @param GithubRepository $repo The repository
     * @param int $number The issue or pull request number
     * @return void
     * @throws Exception
     */
    public function removeAllLabels(GithubRepository $repo, int $number): void
    {
        $requestFactory = new RemoveAllLabelsRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $number
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 204) {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * List repository-level label definitions
     *
     * Distinct from listIssueLabels (which lists assignments on one issue/PR);
     * this lists the labels that exist in the repository.
     *
     * @param GithubRepository $repo The repository
     * @return GithubLabelList
     * @throws Exception
     */
    public function listRepositoryLabels(GithubRepository $repo): GithubLabelList
    {
        $requestFactory = new ListRepositoryLabelsRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo
        );
        $request = $requestFactory->create();
        $labels = [];
        $labelFactory = new GithubLabelFactory();
        while (true) {
            $response = $this->httpClient->sendRequest($request);
            if ($response->getStatusCode() === 200) {
                $data = json_decode((string) $response->getBody());
                foreach ($data as $labelData) {
                    $labels[] = $labelFactory->createFromApiResponse($labelData);
                }
                $pagination = new GithubApiPagination($request, $response);
                if (!$pagination->hasNextLink()) {
                    break;
                }
                $request = $pagination->nextRequest();
            } else {
                throw new Exception($this->parseErrorResponse($response));
            }
        }

        return new GithubLabelList($labels);
    }

    /**
     * Get a single repository-level label by name
     *
     * @param GithubRepository $repo The repository
     * @param string $name The label name
     * @return GithubLabel
     * @throws Exception
     */
    public function getLabel(GithubRepository $repo, string $name): GithubLabel
    {
        $requestFactory = new GetLabelRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $name
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubLabel::fromApiResponse($data);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Create a repository-level label definition
     *
     * @param GithubRepository $repo The repository
     * @param CreateLabelParams $params The label parameters
     * @return GithubLabel The created label
     * @throws Exception
     */
    public function createLabel(GithubRepository $repo, CreateLabelParams $params): GithubLabel
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createLabel. Please provide it in the constructor.');
        }

        $requestFactory = new CreateLabelRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            return GithubLabel::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Update a repository-level label definition
     *
     * The currentName URL segment is the lookup key; a non-null name in
     * UpdateLabelParams renames the label.
     *
     * @param GithubRepository $repo The repository
     * @param string $currentName The current label name (URL key)
     * @param UpdateLabelParams $params The update parameters
     * @return GithubLabel The updated label
     * @throws Exception
     */
    public function updateLabel(GithubRepository $repo, string $currentName, UpdateLabelParams $params): GithubLabel
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for updateLabel. Please provide it in the constructor.');
        }

        $requestFactory = new UpdateLabelRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $currentName,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubLabel::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Delete a repository-level label definition
     *
     * @param GithubRepository $repo The repository
     * @param string $name The label name
     * @return void
     * @throws Exception
     */
    public function deleteLabel(GithubRepository $repo, string $name): void
    {
        $requestFactory = new DeleteLabelRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $name
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 204) {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Merge a pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The pull request number
     * @param MergePullRequestParams $params Merge parameters
     * @return MergeResult The result of the merge operation
     * @throws Exception
     */
    public function mergePullRequest(GithubRepository $repo, int $number, MergePullRequestParams $params): MergeResult
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for mergePullRequest. Please provide it in the constructor.');
        }

        $requestFactory = new MergePullRequestRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $number,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return MergeResult::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Close a pull request without merging
     *
     * @param GithubRepository $repo The repository
     * @param int $number The pull request number
     * @return GithubPullRequest The closed pull request
     * @throws Exception
     */
    public function closePullRequest(GithubRepository $repo, int $number): GithubPullRequest
    {
        $update = new PullRequestUpdate(state: 'closed');
        return $this->updatePullRequest($repo, $number, $update);
    }

    /**
     * Reopen a closed pull request
     *
     * @param GithubRepository $repo The repository
     * @param int $number The pull request number
     * @return GithubPullRequest The reopened pull request
     * @throws Exception
     */
    public function reopenPullRequest(GithubRepository $repo, int $number): GithubPullRequest
    {
        $update = new PullRequestUpdate(state: 'open');
        return $this->updatePullRequest($repo, $number, $update);
    }

    /**
     * Create a new pull request
     *
     * @param GithubRepository $repo The repository
     * @param CreatePullRequestParams $params The pull request parameters
     * @return GithubPullRequest The created pull request
     * @throws Exception
     */
    public function createPullRequest(GithubRepository $repo, CreatePullRequestParams $params): GithubPullRequest
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createPullRequest. Please provide it in the constructor.');
        }

        $requestFactory = new CreatePullRequestRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            $prFactory = new GithubPullRequestFactory();
            return $prFactory->createFromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Create a new release for a tag
     *
     * @param GithubRepository $repo The repository
     * @param CreateReleaseParams $params The release parameters
     * @return GithubRelease The created release
     * @throws Exception
     */
    public function createRelease(GithubRepository $repo, CreateReleaseParams $params): GithubRelease
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createRelease. Please provide it in the constructor.');
        }

        $requestFactory = new CreateReleaseRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            return GithubRelease::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get a release by tag name
     *
     * @param GithubRepository $repo The repository
     * @param string $tag The tag name
     * @return GithubRelease The release
     * @throws Exception
     */
    public function getReleaseByTag(GithubRepository $repo, string $tag): GithubRelease
    {
        $requestFactory = new GetReleaseByTagRequestFactory(
            $this->requestFactory,
            $this->config,
            $repo,
            $tag
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubRelease::fromApiResponse($data);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Update an existing release
     *
     * @param GithubRepository $repo The repository
     * @param int $releaseId The release ID
     * @param UpdateReleaseParams $params The update parameters
     * @return GithubRelease The updated release
     * @throws Exception
     */
    public function updateRelease(GithubRepository $repo, int $releaseId, UpdateReleaseParams $params): GithubRelease
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for updateRelease. Please provide it in the constructor.');
        }

        $requestFactory = new UpdateReleaseRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $repo,
            $releaseId,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubRelease::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Upload an asset to a release
     *
     * @param string $uploadUrl The upload URL from the release object
     * @param string $filename The filename for the asset
     * @param string $fileContent The file content
     * @param string $contentType The MIME type (default: application/octet-stream)
     * @return GithubReleaseAsset The uploaded asset
     * @throws Exception
     */
    public function uploadReleaseAsset(string $uploadUrl, string $filename, string $fileContent, string $contentType = 'application/octet-stream'): GithubReleaseAsset
    {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for uploadReleaseAsset. Please provide it in the constructor.');
        }

        $stream = $this->streamFactory->createStream($fileContent);

        $requestFactory = new UploadReleaseAssetRequestFactory(
            $this->requestFactory,
            $this->config,
            $uploadUrl,
            $filename,
            $stream,
            $contentType
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            return GithubReleaseAsset::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get the authenticated user
     *
     * Retrieves information about the user associated with the current access token.
     * Useful for verifying credentials and getting the current user's identity.
     *
     * @return GithubUser The authenticated user
     * @throws Exception If the request fails
     */
    public function getCurrentUser(): GithubUser
    {
        $requestFactory = new AuthenticatedUserRequestFactory(
            $this->requestFactory,
            $this->config
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubUser::fromApiResponse($data);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Create an installation access token for GitHub App
     *
     * @param int $installationId The installation ID
     * @param CreateInstallationAccessTokenParams $params Optional parameters
     * @return InstallationAccessToken
     * @throws Exception
     */
    public function createInstallationAccessToken(
        int $installationId,
        CreateInstallationAccessTokenParams $params = new CreateInstallationAccessTokenParams()
    ): InstallationAccessToken {
        if ($this->streamFactory === null) {
            throw new Exception('StreamFactory is required for createInstallationAccessToken');
        }

        $requestFactory = new CreateInstallationAccessTokenRequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
            $installationId,
            $params
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 201) {
            $data = json_decode((string) $response->getBody());
            return InstallationAccessToken::fromApiResponse($data);
        } else {
            $this->maybeThrowAccessDenied($response);
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * List all installations of the authenticated GitHub App
     *
     * @return GithubInstallationList
     * @throws Exception
     */
    public function listInstallations(): GithubInstallationList
    {
        $requestFactory = new ListInstallationsRequestFactory(
            $this->requestFactory,
            $this->config
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            $installations = [];
            foreach ($data as $installationData) {
                $installations[] = GithubInstallation::fromApiResponse($installationData);
            }
            return new GithubInstallationList($installations);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get the authenticated GitHub App
     *
     * @return GithubApp
     * @throws Exception
     */
    public function getAuthenticatedApp(): GithubApp
    {
        $requestFactory = new GetAuthenticatedAppRequestFactory(
            $this->requestFactory,
            $this->config
        );
        $request = $requestFactory->create();
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody());
            return GithubApp::fromApiResponse($data);
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * @param array<mixed> $repos
     * @param string $json
     *
     * @return array<array<string|Stringable|int|null>>
     **/
    private function parseJsonAndMerge(array $repos, string $json): array
    {
        $decoded = (array) json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        foreach ($decoded as $repoArray) {
            if (is_array($repoArray)) {
                $repos[] = GithubRepository::isValidArrayRepresentation($repoArray) ? $repoArray : throw new OutOfBoundsException('Element does not contain correct structure');
            } else {
                throw new OutOfBoundsException('List does contain non-list element');
            }
        }
        return $repos;
    }

    /**
     * Detect the "Resource not accessible by integration" 403 and throw a typed exception.
     *
     * GitHub returns this body when an Actions workflow's GITHUB_TOKEN is missing the
     * required scope (e.g. `pull-requests: write` or `checks: write`). The typed
     * exception lets consumers print a maintainer-actionable hint without parsing
     * generic messages. Non-403 statuses and 403 statuses with other body shapes
     * are passed through; the caller falls through to parseErrorResponse().
     *
     * @param ResponseInterface $response The HTTP response
     * @return void
     * @throws GithubApiAccessDeniedException
     */
    private function maybeThrowAccessDenied(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 403) {
            return;
        }

        $body = (string) $response->getBody();
        if ($body === '') {
            return;
        }

        $decoded = json_decode($body);
        if (!is_object($decoded) || !isset($decoded->message) || !is_string($decoded->message)) {
            return;
        }

        if (!str_contains($decoded->message, 'Resource not accessible by integration')) {
            return;
        }

        $hint = 'The GitHub token does not have permission for this operation. '
            . 'Ensure the token has the required scope (e.g. `pull-requests:write`, `checks:write`). '
            . 'See https://docs.github.com/en/rest/authentication.';

        throw new GithubApiAccessDeniedException(
            statusCode: 403,
            responseBody: $body,
            hint: $hint
        );
    }

    /**
     * Parse GitHub API error response and create detailed exception message
     *
     * Extracts detailed error information from GitHub API responses to provide
     * more helpful error messages to users.
     *
     * @param ResponseInterface $response The HTTP response
     * @return string Detailed error message
     */
    private function parseErrorResponse(ResponseInterface $response): string
    {
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();
        $baseMessage = "{$statusCode} {$reasonPhrase}";

        try {
            $body = (string) $response->getBody();
            $errorData = json_decode($body);

            if (!$errorData) {
                // Not JSON or invalid JSON
                return $baseMessage;
            }

            $details = [];

            // GitHub provides detailed errors in an array
            if (isset($errorData->errors) && is_array($errorData->errors)) {
                foreach ($errorData->errors as $error) {
                    if (is_string($error)) {
                        $details[] = $error;
                    } elseif (is_object($error) && isset($error->message)) {
                        $details[] = $error->message;
                    }
                }
            }

            // Fallback to message field
            if (empty($details) && isset($errorData->message) && $errorData->message !== $reasonPhrase) {
                $details[] = $errorData->message;
            }

            if (!empty($details)) {
                return $baseMessage . ': ' . implode('; ', $details);
            }

            return $baseMessage;
        } catch (Exception $e) {
            // If anything goes wrong parsing, return base message
            return $baseMessage;
        }
    }
}
