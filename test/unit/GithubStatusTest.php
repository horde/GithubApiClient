<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubCommitStatus;
use Horde\GithubApiClient\GithubCombinedStatus;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[CoversClass(GithubCommitStatus::class)]
#[CoversClass(GithubCombinedStatus::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubStatusTest extends TestCase
{
    public function testCommitStatusConstructor(): void
    {
        $status = new GithubCommitStatus(
            state: 'success',
            context: 'continuous-integration/travis-ci',
            description: 'The Travis CI build passed',
            targetUrl: 'https://travis-ci.com/owner/repo/builds/12345',
            createdAt: '2026-02-26T10:00:00Z',
            updatedAt: '2026-02-26T10:05:00Z'
        );

        $this->assertSame('success', $status->state);
        $this->assertSame('continuous-integration/travis-ci', $status->context);
        $this->assertSame('The Travis CI build passed', $status->description);
        $this->assertSame('https://travis-ci.com/owner/repo/builds/12345', $status->targetUrl);
        $this->assertSame('2026-02-26T10:00:00Z', $status->createdAt);
        $this->assertSame('2026-02-26T10:05:00Z', $status->updatedAt);
    }

    public function testCommitStatusToString(): void
    {
        $status = new GithubCommitStatus(
            state: 'failure',
            context: 'test/unit',
            description: 'Tests failed',
            targetUrl: '',
            createdAt: '',
            updatedAt: ''
        );

        $this->assertSame('test/unit: failure (Tests failed)', (string) $status);
    }

    public function testCommitStatusFromApiResponse(): void
    {
        $data = (object) [
            'state' => 'pending',
            'context' => 'test/integration',
            'description' => 'Running integration tests',
            'target_url' => 'https://ci.example.com/build/789',
            'created_at' => '2026-02-26T11:00:00Z',
            'updated_at' => '2026-02-26T11:01:00Z'
        ];

        $status = GithubCommitStatus::fromApiResponse($data);

        $this->assertSame('pending', $status->state);
        $this->assertSame('test/integration', $status->context);
        $this->assertSame('Running integration tests', $status->description);
        $this->assertSame('https://ci.example.com/build/789', $status->targetUrl);
        $this->assertSame('2026-02-26T11:00:00Z', $status->createdAt);
        $this->assertSame('2026-02-26T11:01:00Z', $status->updatedAt);
    }

    public function testCommitStatusFromApiResponseWithMinimalData(): void
    {
        $data = (object) [];

        $status = GithubCommitStatus::fromApiResponse($data);

        $this->assertSame('', $status->state);
        $this->assertSame('', $status->context);
        $this->assertSame('', $status->description);
        $this->assertSame('', $status->targetUrl);
        $this->assertSame('', $status->createdAt);
        $this->assertSame('', $status->updatedAt);
    }

    public function testCombinedStatusConstructor(): void
    {
        $status1 = new GithubCommitStatus('success', 'ci/travis', 'Passed', '', '', '');
        $status2 = new GithubCommitStatus('success', 'ci/github-actions', 'Passed', '', '', '');

        $combined = new GithubCombinedStatus(
            state: 'success',
            sha: 'abc123def456',
            totalCount: 2,
            statuses: [$status1, $status2]
        );

        $this->assertSame('success', $combined->state);
        $this->assertSame('abc123def456', $combined->sha);
        $this->assertSame(2, $combined->totalCount);
        $this->assertCount(2, $combined->statuses);
        $this->assertSame($status1, $combined->statuses[0]);
        $this->assertSame($status2, $combined->statuses[1]);
    }

    public function testCombinedStatusToString(): void
    {
        $combined = new GithubCombinedStatus(
            state: 'pending',
            sha: 'abc123',
            totalCount: 3,
            statuses: []
        );

        $this->assertSame('pending (3 checks)', (string) $combined);
    }

    public function testCombinedStatusFromApiResponse(): void
    {
        $data = (object) [
            'state' => 'success',
            'sha' => 'def789ghi012',
            'total_count' => 2,
            'statuses' => [
                (object) [
                    'state' => 'success',
                    'context' => 'test1',
                    'description' => 'Test 1 passed',
                    'target_url' => 'https://ci.example.com/1',
                    'created_at' => '2026-02-26T12:00:00Z',
                    'updated_at' => '2026-02-26T12:01:00Z'
                ],
                (object) [
                    'state' => 'success',
                    'context' => 'test2',
                    'description' => 'Test 2 passed',
                    'target_url' => 'https://ci.example.com/2',
                    'created_at' => '2026-02-26T12:00:00Z',
                    'updated_at' => '2026-02-26T12:02:00Z'
                ]
            ]
        ];

        $combined = GithubCombinedStatus::fromApiResponse($data);

        $this->assertSame('success', $combined->state);
        $this->assertSame('def789ghi012', $combined->sha);
        $this->assertSame(2, $combined->totalCount);
        $this->assertCount(2, $combined->statuses);
        $this->assertSame('test1', $combined->statuses[0]->context);
        $this->assertSame('test2', $combined->statuses[1]->context);
    }

    public function testCombinedStatusFromApiResponseWithMinimalData(): void
    {
        $data = (object) [];

        $combined = GithubCombinedStatus::fromApiResponse($data);

        $this->assertSame('', $combined->state);
        $this->assertSame('', $combined->sha);
        $this->assertSame(0, $combined->totalCount);
        $this->assertCount(0, $combined->statuses);
    }

    public function testCombinedStatusStates(): void
    {
        $successCombined = new GithubCombinedStatus('success', 'abc123', 1, []);
        $this->assertSame('success', $successCombined->state);

        $failureCombined = new GithubCombinedStatus('failure', 'def456', 1, []);
        $this->assertSame('failure', $failureCombined->state);

        $pendingCombined = new GithubCombinedStatus('pending', 'ghi789', 1, []);
        $this->assertSame('pending', $pendingCombined->state);
    }
}
