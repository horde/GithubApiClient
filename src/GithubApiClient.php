<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Horde\Http\RequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
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
                throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
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
            throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
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
            throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
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
            throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
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
            throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
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
            throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
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
            throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
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
            throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
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
            throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
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
            throw new Exception($response->getStatusCode() . ' ' . $response->getReasonPhrase());
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
}
