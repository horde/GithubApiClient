<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubReview;
use Horde\GithubApiClient\GithubUser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GithubReview::class)]
class GithubReviewTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $user = new GithubUser(
            login: 'reviewer',
            id: 456,
            avatarUrl: 'https://avatar.example.com/reviewer.png',
            htmlUrl: 'https://github.com/reviewer',
            type: 'User'
        );

        $review = new GithubReview(
            id: 789123,
            user: $user,
            body: 'Looks good to me',
            state: 'APPROVED',
            htmlUrl: 'https://github.com/owner/repo/pull/1#pullrequestreview-789123',
            submittedAt: '2026-02-26T12:00:00Z',
            commitId: 'abc123def456'
        );

        $this->assertSame(789123, $review->id);
        $this->assertSame($user, $review->user);
        $this->assertSame('Looks good to me', $review->body);
        $this->assertSame('APPROVED', $review->state);
        $this->assertSame('https://github.com/owner/repo/pull/1#pullrequestreview-789123', $review->htmlUrl);
        $this->assertSame('2026-02-26T12:00:00Z', $review->submittedAt);
        $this->assertSame('abc123def456', $review->commitId);
    }

    public function testToStringReturnsHtmlUrl(): void
    {
        $user = new GithubUser(
            login: 'reviewer',
            id: 456,
            avatarUrl: 'https://avatar.example.com/reviewer.png',
            htmlUrl: 'https://github.com/reviewer',
            type: 'User'
        );

        $review = new GithubReview(
            id: 789123,
            user: $user,
            body: 'Test',
            state: 'APPROVED',
            htmlUrl: 'https://github.com/owner/repo/pull/1#pullrequestreview-789123',
            submittedAt: '2026-02-26T12:00:00Z',
            commitId: 'abc123'
        );

        $this->assertSame('https://github.com/owner/repo/pull/1#pullrequestreview-789123', (string) $review);
    }

    public function testFromApiResponseWithCompleteData(): void
    {
        $data = (object) [
            'id' => 111222,
            'user' => (object) [
                'login' => 'apireviewer',
                'id' => 333,
                'avatar_url' => 'https://avatar.example.com/apireviewer.png',
                'html_url' => 'https://github.com/apireviewer',
                'type' => 'User'
            ],
            'body' => 'API review comment',
            'state' => 'CHANGES_REQUESTED',
            'html_url' => 'https://github.com/org/repo/pull/5#pullrequestreview-111222',
            'submitted_at' => '2026-02-15T14:30:00Z',
            'commit_id' => 'def789ghi012'
        ];

        $review = GithubReview::fromApiResponse($data);

        $this->assertSame(111222, $review->id);
        $this->assertSame('apireviewer', $review->user->login);
        $this->assertSame('API review comment', $review->body);
        $this->assertSame('CHANGES_REQUESTED', $review->state);
        $this->assertSame('https://github.com/org/repo/pull/5#pullrequestreview-111222', $review->htmlUrl);
        $this->assertSame('2026-02-15T14:30:00Z', $review->submittedAt);
        $this->assertSame('def789ghi012', $review->commitId);
    }

    public function testFromApiResponseWithMinimalData(): void
    {
        $data = (object) [
            'user' => (object) []
        ];

        $review = GithubReview::fromApiResponse($data);

        $this->assertSame(0, $review->id);
        $this->assertSame('', $review->user->login);
        $this->assertSame('', $review->body);
        $this->assertSame('', $review->state);
        $this->assertSame('', $review->htmlUrl);
        $this->assertSame('', $review->submittedAt);
        $this->assertSame('', $review->commitId);
    }

    public function testReviewStates(): void
    {
        $user = new GithubUser('reviewer', 123, '', '', 'User');

        // Test APPROVED state
        $approvedReview = new GithubReview(1, $user, 'LGTM', 'APPROVED', '', '', '');
        $this->assertSame('APPROVED', $approvedReview->state);

        // Test CHANGES_REQUESTED state
        $changesReview = new GithubReview(2, $user, 'Needs work', 'CHANGES_REQUESTED', '', '', '');
        $this->assertSame('CHANGES_REQUESTED', $changesReview->state);

        // Test COMMENTED state
        $commentedReview = new GithubReview(3, $user, 'Some thoughts', 'COMMENTED', '', '', '');
        $this->assertSame('COMMENTED', $commentedReview->state);

        // Test DISMISSED state
        $dismissedReview = new GithubReview(4, $user, 'Old review', 'DISMISSED', '', '', '');
        $this->assertSame('DISMISSED', $dismissedReview->state);
    }
}
