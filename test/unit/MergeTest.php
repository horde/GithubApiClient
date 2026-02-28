<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\MergePullRequestParams;
use Horde\GithubApiClient\MergeResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[CoversClass(MergePullRequestParams::class)]
#[CoversClass(MergeResult::class)]
#[AllowMockObjectsWithoutExpectations]
class MergeTest extends TestCase
{
    public function testMergePullRequestParamsConstructorDefaults(): void
    {
        $params = new MergePullRequestParams();

        $this->assertSame('', $params->commitTitle);
        $this->assertSame('', $params->commitMessage);
        $this->assertSame('merge', $params->mergeMethod);
        $this->assertSame('', $params->sha);
    }

    public function testMergePullRequestParamsConstructorWithAllParameters(): void
    {
        $params = new MergePullRequestParams(
            commitTitle: 'Merge pull request #123',
            commitMessage: 'This PR adds feature X',
            mergeMethod: 'squash',
            sha: 'abc123def456'
        );

        $this->assertSame('Merge pull request #123', $params->commitTitle);
        $this->assertSame('This PR adds feature X', $params->commitMessage);
        $this->assertSame('squash', $params->mergeMethod);
        $this->assertSame('abc123def456', $params->sha);
    }

    public function testMergePullRequestParamsToArrayWithDefaults(): void
    {
        $params = new MergePullRequestParams();
        $array = $params->toArray();

        $this->assertSame([], $array);
    }

    public function testMergePullRequestParamsToArrayWithAllFields(): void
    {
        $params = new MergePullRequestParams(
            commitTitle: 'Test title',
            commitMessage: 'Test message',
            mergeMethod: 'squash',
            sha: 'abc123'
        );
        $array = $params->toArray();

        $this->assertSame([
            'commit_title' => 'Test title',
            'commit_message' => 'Test message',
            'merge_method' => 'squash',
            'sha' => 'abc123'
        ], $array);
    }

    public function testMergePullRequestParamsToArrayExcludesDefaultMergeMethod(): void
    {
        $params = new MergePullRequestParams(
            commitTitle: 'Test',
            mergeMethod: 'merge'
        );
        $array = $params->toArray();

        $this->assertArrayHasKey('commit_title', $array);
        $this->assertArrayNotHasKey('merge_method', $array);
    }

    public function testMergePullRequestParamsMergeMethods(): void
    {
        // merge
        $mergeParams = new MergePullRequestParams(mergeMethod: 'merge');
        $this->assertSame('merge', $mergeParams->mergeMethod);

        // squash
        $squashParams = new MergePullRequestParams(mergeMethod: 'squash');
        $this->assertSame('squash', $squashParams->mergeMethod);

        // rebase
        $rebaseParams = new MergePullRequestParams(mergeMethod: 'rebase');
        $this->assertSame('rebase', $rebaseParams->mergeMethod);
    }

    public function testMergeResultConstructor(): void
    {
        $result = new MergeResult(
            sha: 'def789ghi012',
            merged: true,
            message: 'Pull Request successfully merged'
        );

        $this->assertSame('def789ghi012', $result->sha);
        $this->assertTrue($result->merged);
        $this->assertSame('Pull Request successfully merged', $result->message);
    }

    public function testMergeResultToStringWhenMerged(): void
    {
        $result = new MergeResult(
            sha: 'abc123',
            merged: true,
            message: 'Merged successfully'
        );

        $this->assertSame('Merged: Merged successfully (abc123)', (string) $result);
    }

    public function testMergeResultToStringWhenNotMerged(): void
    {
        $result = new MergeResult(
            sha: '',
            merged: false,
            message: 'Pull request is not mergeable'
        );

        $this->assertSame('Not merged: Pull request is not mergeable', (string) $result);
    }

    public function testMergeResultFromApiResponseWhenMerged(): void
    {
        $data = (object) [
            'sha' => 'ghi789jkl012',
            'merged' => true,
            'message' => 'Pull Request successfully merged'
        ];

        $result = MergeResult::fromApiResponse($data);

        $this->assertSame('ghi789jkl012', $result->sha);
        $this->assertTrue($result->merged);
        $this->assertSame('Pull Request successfully merged', $result->message);
    }

    public function testMergeResultFromApiResponseWhenNotMerged(): void
    {
        $data = (object) [
            'sha' => '',
            'merged' => false,
            'message' => 'Merge conflict'
        ];

        $result = MergeResult::fromApiResponse($data);

        $this->assertSame('', $result->sha);
        $this->assertFalse($result->merged);
        $this->assertSame('Merge conflict', $result->message);
    }

    public function testMergeResultFromApiResponseWithMinimalData(): void
    {
        $data = (object) [];

        $result = MergeResult::fromApiResponse($data);

        $this->assertSame('', $result->sha);
        $this->assertFalse($result->merged);
        $this->assertSame('', $result->message);
    }
}
