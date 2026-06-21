<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use DateTimeImmutable;
use Horde\GithubApiClient\CreateMilestoneParams;
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
#[CoversClass(CreateMilestoneParams::class)]
final class CreateMilestoneParamsTest extends TestCase
{
    public function testTitleOnly(): void
    {
        $params = new CreateMilestoneParams(title: 'v2');

        $this->assertSame(['title' => 'v2', 'state' => 'open'], $params->toArray());
    }

    public function testDueOnEmittedAsIso8601(): void
    {
        $due = new DateTimeImmutable('2026-07-31T23:59:59+00:00');
        $params = new CreateMilestoneParams(title: 'v2', dueOn: $due);

        $this->assertSame('2026-07-31T23:59:59+00:00', $params->toArray()['due_on']);
    }

    public function testDescriptionEmittedWhenNonEmpty(): void
    {
        $params = new CreateMilestoneParams(title: 'v2', description: 'Major');

        $this->assertSame(
            ['title' => 'v2', 'state' => 'open', 'description' => 'Major'],
            $params->toArray()
        );
    }
}
