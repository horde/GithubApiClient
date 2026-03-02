<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubComment;
use Horde\GithubApiClient\GithubUser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[CoversClass(GithubComment::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubCommentTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $author = new GithubUser(
            login: 'testuser',
            id: 123,
            avatarUrl: 'https://avatar.example.com/test.png',
            htmlUrl: 'https://github.com/testuser'
        );

        $comment = new GithubComment(
            id: 456789,
            body: 'This is a test comment',
            author: $author,
            createdAt: '2026-02-26T10:00:00Z',
            updatedAt: '2026-02-26T11:00:00Z',
            htmlUrl: 'https://github.com/owner/repo/pull/1#issuecomment-456789',
            apiUrl: 'https://api.github.com/repos/owner/repo/issues/comments/456789'
        );

        $this->assertSame(456789, $comment->id);
        $this->assertSame('This is a test comment', $comment->body);
        $this->assertSame($author, $comment->author);
        $this->assertSame('2026-02-26T10:00:00Z', $comment->createdAt);
        $this->assertSame('2026-02-26T11:00:00Z', $comment->updatedAt);
        $this->assertSame('https://github.com/owner/repo/pull/1#issuecomment-456789', $comment->htmlUrl);
        $this->assertSame('https://api.github.com/repos/owner/repo/issues/comments/456789', $comment->apiUrl);
    }

    public function testToStringReturnsHtmlUrl(): void
    {
        $author = new GithubUser(
            login: 'testuser',
            id: 123,
            avatarUrl: 'https://avatar.example.com/test.png',
            htmlUrl: 'https://github.com/testuser'
        );

        $comment = new GithubComment(
            id: 456789,
            body: 'Test',
            author: $author,
            createdAt: '2026-02-26T10:00:00Z',
            updatedAt: '2026-02-26T10:00:00Z',
            htmlUrl: 'https://github.com/owner/repo/pull/1#issuecomment-456789',
            apiUrl: 'https://api.github.com/repos/owner/repo/issues/comments/456789'
        );

        $this->assertSame('https://github.com/owner/repo/pull/1#issuecomment-456789', (string) $comment);
    }

    public function testFromApiResponseWithCompleteData(): void
    {
        $data = (object) [
            'id' => 999888,
            'body' => 'API comment body',
            'user' => (object) [
                'login' => 'apiuser',
                'id' => 777,
                'avatar_url' => 'https://avatar.example.com/api.png',
                'html_url' => 'https://github.com/apiuser',
                'type' => 'User',
            ],
            'created_at' => '2026-01-15T08:30:00Z',
            'updated_at' => '2026-01-15T09:45:00Z',
            'html_url' => 'https://github.com/org/repo/pull/5#issuecomment-999888',
            'url' => 'https://api.github.com/repos/org/repo/issues/comments/999888',
        ];

        $comment = GithubComment::fromApiResponse($data);

        $this->assertSame(999888, $comment->id);
        $this->assertSame('API comment body', $comment->body);
        $this->assertSame('apiuser', $comment->author->login);
        $this->assertSame('2026-01-15T08:30:00Z', $comment->createdAt);
        $this->assertSame('2026-01-15T09:45:00Z', $comment->updatedAt);
        $this->assertSame('https://github.com/org/repo/pull/5#issuecomment-999888', $comment->htmlUrl);
        $this->assertSame('https://api.github.com/repos/org/repo/issues/comments/999888', $comment->apiUrl);
    }

    public function testFromApiResponseWithMinimalData(): void
    {
        $data = (object) [
            'user' => (object) [],
        ];

        $comment = GithubComment::fromApiResponse($data);

        $this->assertSame(0, $comment->id);
        $this->assertSame('', $comment->body);
        $this->assertSame('', $comment->author->login);
        $this->assertSame('', $comment->createdAt);
        $this->assertSame('', $comment->updatedAt);
        $this->assertSame('', $comment->htmlUrl);
        $this->assertSame('', $comment->apiUrl);
    }
}
