<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

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
#[CoversClass(CreateReviewParams::class)]
#[AllowMockObjectsWithoutExpectations]
class CreateReviewParamsTest extends TestCase
{
    public function testApproveConstructionMinimal(): void
    {
        $params = new CreateReviewParams(
            event: 'APPROVE',
        );

        $this->assertSame('APPROVE', $params->event);
        $this->assertSame('', $params->body);
        $this->assertSame('', $params->commitId);
    }

    public function testApproveConstructionWithBody(): void
    {
        $params = new CreateReviewParams(
            event: 'APPROVE',
            body: 'Looks good to me!',
        );

        $this->assertSame('APPROVE', $params->event);
        $this->assertSame('Looks good to me!', $params->body);
        $this->assertSame('', $params->commitId);
    }

    public function testRequestChangesConstruction(): void
    {
        $params = new CreateReviewParams(
            event: 'REQUEST_CHANGES',
            body: 'Please fix the formatting',
        );

        $this->assertSame('REQUEST_CHANGES', $params->event);
        $this->assertSame('Please fix the formatting', $params->body);
        $this->assertSame('', $params->commitId);
    }

    public function testCommentConstruction(): void
    {
        $params = new CreateReviewParams(
            event: 'COMMENT',
            body: 'Just a comment',
        );

        $this->assertSame('COMMENT', $params->event);
        $this->assertSame('Just a comment', $params->body);
        $this->assertSame('', $params->commitId);
    }

    public function testConstructionWithCommitId(): void
    {
        $params = new CreateReviewParams(
            event: 'APPROVE',
            commitId: 'abc123',
        );

        $this->assertSame('APPROVE', $params->event);
        $this->assertSame('', $params->body);
        $this->assertSame('abc123', $params->commitId);
    }

    public function testInvalidEventThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid event: INVALID');

        new CreateReviewParams(
            event: 'INVALID',
        );
    }

    public function testRequestChangesWithoutBodyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Body is required when event is REQUEST_CHANGES');

        new CreateReviewParams(
            event: 'REQUEST_CHANGES',
        );
    }

    public function testToArrayApproveMinimal(): void
    {
        $params = new CreateReviewParams(
            event: 'APPROVE',
        );

        $array = $params->toArray();

        $this->assertSame([
            'event' => 'APPROVE',
        ], $array);
    }

    public function testToArrayApproveWithBody(): void
    {
        $params = new CreateReviewParams(
            event: 'APPROVE',
            body: 'Looks good!',
        );

        $array = $params->toArray();

        $this->assertSame([
            'event' => 'APPROVE',
            'body' => 'Looks good!',
        ], $array);
    }

    public function testToArrayRequestChanges(): void
    {
        $params = new CreateReviewParams(
            event: 'REQUEST_CHANGES',
            body: 'Please fix these issues',
        );

        $array = $params->toArray();

        $this->assertSame([
            'event' => 'REQUEST_CHANGES',
            'body' => 'Please fix these issues',
        ], $array);
    }

    public function testToArrayWithCommitId(): void
    {
        $params = new CreateReviewParams(
            event: 'APPROVE',
            body: 'LGTM',
            commitId: 'abc123def456',
        );

        $array = $params->toArray();

        $this->assertSame([
            'event' => 'APPROVE',
            'body' => 'LGTM',
            'commit_id' => 'abc123def456',
        ], $array);
    }

    public function testToArrayComment(): void
    {
        $params = new CreateReviewParams(
            event: 'COMMENT',
            body: 'Just leaving a comment',
        );

        $array = $params->toArray();

        $this->assertSame([
            'event' => 'COMMENT',
            'body' => 'Just leaving a comment',
        ], $array);
    }
}
