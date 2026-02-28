<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubCheckRun;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[CoversClass(GithubCheckRun::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubCheckRunTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $checkRun = new GithubCheckRun(
            id: 123456,
            name: 'Build and Test',
            status: 'completed',
            conclusion: 'success',
            headSha: 'abc123def456',
            htmlUrl: 'https://github.com/owner/repo/runs/123456',
            detailsUrl: 'https://github.com/owner/repo/runs/123456/details',
            startedAt: '2026-02-26T10:00:00Z',
            completedAt: '2026-02-26T10:05:00Z'
        );

        $this->assertSame(123456, $checkRun->id);
        $this->assertSame('Build and Test', $checkRun->name);
        $this->assertSame('completed', $checkRun->status);
        $this->assertSame('success', $checkRun->conclusion);
        $this->assertSame('abc123def456', $checkRun->headSha);
        $this->assertSame('https://github.com/owner/repo/runs/123456', $checkRun->htmlUrl);
        $this->assertSame('https://github.com/owner/repo/runs/123456/details', $checkRun->detailsUrl);
        $this->assertSame('2026-02-26T10:00:00Z', $checkRun->startedAt);
        $this->assertSame('2026-02-26T10:05:00Z', $checkRun->completedAt);
    }

    public function testToString(): void
    {
        $checkRun = new GithubCheckRun(
            id: 789,
            name: 'Lint',
            status: 'completed',
            conclusion: 'failure',
            headSha: 'def789',
            htmlUrl: '',
            detailsUrl: '',
            startedAt: '',
            completedAt: ''
        );

        $this->assertSame('Lint: completed/failure', (string) $checkRun);
    }

    public function testFromApiResponseWithCompleteData(): void
    {
        $data = (object) [
            'id' => 999888,
            'name' => 'CodeQL Analysis',
            'status' => 'in_progress',
            'conclusion' => null,
            'head_sha' => 'ghi789jkl012',
            'html_url' => 'https://github.com/org/repo/runs/999888',
            'details_url' => 'https://github.com/org/repo/runs/999888/details',
            'started_at' => '2026-02-26T11:00:00Z',
            'completed_at' => null
        ];

        $checkRun = GithubCheckRun::fromApiResponse($data);

        $this->assertSame(999888, $checkRun->id);
        $this->assertSame('CodeQL Analysis', $checkRun->name);
        $this->assertSame('in_progress', $checkRun->status);
        $this->assertSame('', $checkRun->conclusion);
        $this->assertSame('ghi789jkl012', $checkRun->headSha);
        $this->assertSame('https://github.com/org/repo/runs/999888', $checkRun->htmlUrl);
        $this->assertSame('https://github.com/org/repo/runs/999888/details', $checkRun->detailsUrl);
        $this->assertSame('2026-02-26T11:00:00Z', $checkRun->startedAt);
        $this->assertSame('', $checkRun->completedAt);
    }

    public function testFromApiResponseWithMinimalData(): void
    {
        $data = (object) [];

        $checkRun = GithubCheckRun::fromApiResponse($data);

        $this->assertSame(0, $checkRun->id);
        $this->assertSame('', $checkRun->name);
        $this->assertSame('', $checkRun->status);
        $this->assertSame('', $checkRun->conclusion);
        $this->assertSame('', $checkRun->headSha);
        $this->assertSame('', $checkRun->htmlUrl);
        $this->assertSame('', $checkRun->detailsUrl);
        $this->assertSame('', $checkRun->startedAt);
        $this->assertSame('', $checkRun->completedAt);
    }

    public function testCheckRunStatuses(): void
    {
        // queued
        $queuedRun = new GithubCheckRun(1, 'Test', 'queued', '', '', '', '', '', '');
        $this->assertSame('queued', $queuedRun->status);

        // in_progress
        $inProgressRun = new GithubCheckRun(2, 'Test', 'in_progress', '', '', '', '', '', '');
        $this->assertSame('in_progress', $inProgressRun->status);

        // completed
        $completedRun = new GithubCheckRun(3, 'Test', 'completed', '', '', '', '', '', '');
        $this->assertSame('completed', $completedRun->status);
    }

    public function testCheckRunConclusions(): void
    {
        // success
        $successRun = new GithubCheckRun(1, 'Test', 'completed', 'success', '', '', '', '', '');
        $this->assertSame('success', $successRun->conclusion);

        // failure
        $failureRun = new GithubCheckRun(2, 'Test', 'completed', 'failure', '', '', '', '', '');
        $this->assertSame('failure', $failureRun->conclusion);

        // neutral
        $neutralRun = new GithubCheckRun(3, 'Test', 'completed', 'neutral', '', '', '', '', '');
        $this->assertSame('neutral', $neutralRun->conclusion);

        // cancelled
        $cancelledRun = new GithubCheckRun(4, 'Test', 'completed', 'cancelled', '', '', '', '', '');
        $this->assertSame('cancelled', $cancelledRun->conclusion);

        // skipped
        $skippedRun = new GithubCheckRun(5, 'Test', 'completed', 'skipped', '', '', '', '', '');
        $this->assertSame('skipped', $skippedRun->conclusion);

        // timed_out
        $timedOutRun = new GithubCheckRun(6, 'Test', 'completed', 'timed_out', '', '', '', '', '');
        $this->assertSame('timed_out', $timedOutRun->conclusion);

        // action_required
        $actionRequiredRun = new GithubCheckRun(7, 'Test', 'completed', 'action_required', '', '', '', '', '');
        $this->assertSame('action_required', $actionRequiredRun->conclusion);
    }
}
