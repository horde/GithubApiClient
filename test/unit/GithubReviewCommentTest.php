<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubReviewComment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
#[CoversClass(GithubReviewComment::class)]
class GithubReviewCommentTest extends TestCase
{
    public function testFromApiResponseMapsAllFields(): void
    {
        $user = new stdClass();
        $user->login = 'octocat';

        $data = new stdClass();
        $data->id = 77;
        $data->body = 'Found it';
        $data->path = 'src/Foo.php';
        $data->line = 12;
        $data->start_line = 10;
        $data->side = 'RIGHT';
        $data->commit_id = 'sha-abc';
        $data->user = $user;
        $data->html_url = 'https://github.com/owner/repo/pull/1#discussion_r77';
        $data->created_at = '2026-06-24T10:00:00Z';
        $data->updated_at = '2026-06-24T10:05:00Z';

        $comment = GithubReviewComment::fromApiResponse($data);

        $this->assertSame(77, $comment->id);
        $this->assertSame('Found it', $comment->body);
        $this->assertSame('src/Foo.php', $comment->path);
        $this->assertSame(12, $comment->line);
        $this->assertSame(10, $comment->startLine);
        $this->assertSame('RIGHT', $comment->side);
        $this->assertSame('sha-abc', $comment->commitId);
        $this->assertSame('octocat', $comment->userLogin);
        $this->assertSame('https://github.com/owner/repo/pull/1#discussion_r77', $comment->htmlUrl);
        $this->assertSame('2026-06-24T10:00:00Z', $comment->createdAt);
        $this->assertSame('2026-06-24T10:05:00Z', $comment->updatedAt);
    }

    public function testFromApiResponseNullStartLineForSingleLine(): void
    {
        $user = new stdClass();
        $user->login = 'bot';

        $data = new stdClass();
        $data->id = 1;
        $data->body = '';
        $data->path = 'a';
        $data->line = 1;
        $data->side = 'RIGHT';
        $data->commit_id = 'x';
        $data->user = $user;
        $data->html_url = '';
        $data->created_at = '';
        $data->updated_at = '';
        // start_line intentionally omitted

        $comment = GithubReviewComment::fromApiResponse($data);

        $this->assertNull($comment->startLine);
    }

    public function testStringableFormat(): void
    {
        $user = new stdClass();
        $user->login = 'reviewer';

        $data = new stdClass();
        $data->id = 1;
        $data->body = 'x';
        $data->path = 'lib/Bar.php';
        $data->line = 99;
        $data->side = 'RIGHT';
        $data->commit_id = '';
        $data->user = $user;
        $data->html_url = '';
        $data->created_at = '';
        $data->updated_at = '';

        $comment = GithubReviewComment::fromApiResponse($data);

        $this->assertSame('lib/Bar.php:99 by reviewer', (string) $comment);
    }
}
