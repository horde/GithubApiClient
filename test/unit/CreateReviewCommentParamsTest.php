<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\CreateReviewCommentParams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
#[CoversClass(CreateReviewCommentParams::class)]
class CreateReviewCommentParamsTest extends TestCase
{
    public function testRequiredFieldsAndDefaults(): void
    {
        $params = new CreateReviewCommentParams(
            body: 'Looks off here',
            commitId: 'sha123',
            path: 'src/Foo.php',
            line: 42,
        );

        $this->assertSame('RIGHT', $params->side);
        $this->assertSame('line', $params->subjectType);
        $this->assertNull($params->startLine);
        $this->assertNull($params->startSide);
        $this->assertNull($params->inReplyTo);
    }

    public function testToArrayEmitsRequiredFieldsWithSnakeCase(): void
    {
        $params = new CreateReviewCommentParams(
            body: 'Issue here',
            commitId: 'deadbeef',
            path: 'lib/Bar.php',
            line: 7,
        );

        $array = $params->toArray();

        $this->assertSame('Issue here', $array['body']);
        $this->assertSame('deadbeef', $array['commit_id']);
        $this->assertSame('lib/Bar.php', $array['path']);
        $this->assertSame(7, $array['line']);
        $this->assertSame('RIGHT', $array['side']);
        $this->assertSame('line', $array['subject_type']);
        $this->assertArrayNotHasKey('commitId', $array);
        $this->assertArrayNotHasKey('subjectType', $array);
        $this->assertArrayNotHasKey('start_line', $array);
        $this->assertArrayNotHasKey('start_side', $array);
        $this->assertArrayNotHasKey('in_reply_to', $array);
    }

    public function testToArrayWithMultiLineFields(): void
    {
        $params = new CreateReviewCommentParams(
            body: 'Block issue',
            commitId: 'sha',
            path: 'src/X.php',
            line: 50,
            startLine: 45,
            startSide: 'RIGHT',
        );

        $array = $params->toArray();

        $this->assertSame(45, $array['start_line']);
        $this->assertSame('RIGHT', $array['start_side']);
    }

    public function testToArrayWithInReplyTo(): void
    {
        $params = new CreateReviewCommentParams(
            body: 'Reply',
            commitId: 'sha',
            path: 'src/X.php',
            line: 1,
            inReplyTo: 9999,
        );

        $array = $params->toArray();

        $this->assertSame(9999, $array['in_reply_to']);
    }

    public function testSideAndSubjectTypeOverrides(): void
    {
        $params = new CreateReviewCommentParams(
            body: 'Old line gone',
            commitId: 'sha',
            path: 'src/X.php',
            line: 3,
            side: 'LEFT',
            subjectType: 'file',
        );

        $array = $params->toArray();

        $this->assertSame('LEFT', $array['side']);
        $this->assertSame('file', $array['subject_type']);
    }
}
