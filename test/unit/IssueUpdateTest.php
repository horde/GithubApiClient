<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\IssueUpdate;
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
#[CoversClass(IssueUpdate::class)]
final class IssueUpdateTest extends TestCase
{
    public function testFreshlyConstructedIsEmpty(): void
    {
        $update = new IssueUpdate();

        $this->assertTrue($update->isEmpty());
        $this->assertSame([], $update->toArray());
    }

    public function testUntouchedFieldsOmitted(): void
    {
        $update = (new IssueUpdate())->withTitle('hello');

        $this->assertSame(['title' => 'hello'], $update->toArray());
    }

    public function testWithMilestoneNullEmitsLiteralNull(): void
    {
        // Critical for the "clear assignment" GitHub semantic.
        $update = (new IssueUpdate())->withMilestone(null);

        $array = $update->toArray();
        $this->assertArrayHasKey('milestone', $array);
        $this->assertNull($array['milestone']);
    }

    public function testWithTypeNullEmitsLiteralNull(): void
    {
        $update = (new IssueUpdate())->withType(null);

        $array = $update->toArray();
        $this->assertArrayHasKey('type', $array);
        $this->assertNull($array['type']);
    }

    public function testWithMilestoneValueEmitsInteger(): void
    {
        $update = (new IssueUpdate())->withMilestone(7);

        $this->assertSame(['milestone' => 7], $update->toArray());
    }

    public function testWithTypeValueEmitsString(): void
    {
        $update = (new IssueUpdate())->withType('Bug');

        $this->assertSame(['type' => 'Bug'], $update->toArray());
    }

    public function testLastSetWins(): void
    {
        // Builder returns clones, so re-applying overrides cleanly.
        $update = (new IssueUpdate())
            ->withMilestone(7)
            ->withMilestone(null);

        $array = $update->toArray();
        $this->assertArrayHasKey('milestone', $array);
        $this->assertNull($array['milestone']);
    }

    public function testWithLabelsEmpty(): void
    {
        // [] is a valid value: clear all labels.
        $update = (new IssueUpdate())->withLabels([]);

        $this->assertSame(['labels' => []], $update->toArray());
    }

    public function testWithAssigneesEmpty(): void
    {
        $update = (new IssueUpdate())->withAssignees([]);

        $this->assertSame(['assignees' => []], $update->toArray());
    }

    public function testWithStateAndReason(): void
    {
        $update = (new IssueUpdate())
            ->withState('closed')
            ->withStateReason('not_planned');

        $this->assertSame(
            ['state' => 'closed', 'state_reason' => 'not_planned'],
            $update->toArray()
        );
    }

    public function testBuilderReturnsClonesNotMutates(): void
    {
        $original = new IssueUpdate();
        $modified = $original->withTitle('changed');

        $this->assertTrue($original->isEmpty());
        $this->assertFalse($modified->isEmpty());
        $this->assertNotSame($original, $modified);
    }
}
