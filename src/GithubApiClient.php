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
            throw new Exception($this->parseErrorResponse($response));
        }
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
        } catch (\Exception $e) {
            // If anything goes wrong parsing, return base message
            return $baseMessage;
        }
    }
}
