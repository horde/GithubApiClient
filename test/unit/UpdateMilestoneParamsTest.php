<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use DateTimeImmutable;
use Horde\GithubApiClient\UpdateMilestoneParams;
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
#[CoversClass(UpdateMilestoneParams::class)]
final class UpdateMilestoneParamsTest extends TestCase
{
    public function testAllNullReturnsEmpty(): void
    {
        $this->assertSame([], (new UpdateMilestoneParams())->toArray());
    }

    public function testPartialUpdate(): void
    {
        $params = new UpdateMilestoneParams(state: 'closed');

        $this->assertSame(['state' => 'closed'], $params->toArray());
    }

    public function testDueOnEmittedAsIso8601(): void
    {
        $due = new DateTimeImmutable('2026-07-31T23:59:59+00:00');
        $params = new UpdateMilestoneParams(dueOn: $due);

        $this->assertSame(['due_on' => '2026-07-31T23:59:59+00:00'], $params->toArray());
    }
}
