<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\CreateIssueParams;
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
#[CoversClass(CreateIssueParams::class)]
final class CreateIssueParamsTest extends TestCase
{
    public function testTitleOnly(): void
    {
        $params = new CreateIssueParams(title: 'Crash on launch');

        $this->assertSame(['title' => 'Crash on launch'], $params->toArray());
    }

    public function testAllFields(): void
    {
        $params = new CreateIssueParams(
            title: 'Crash',
            body: 'Steps to reproduce',
            assignees: ['alice', 'bob'],
            labels: ['bug', 'urgent'],
            milestone: 7,
            type: 'Bug',
        );

        $this->assertSame(
            [
                'title' => 'Crash',
                'body' => 'Steps to reproduce',
                'assignees' => ['alice', 'bob'],
                'labels' => ['bug', 'urgent'],
                'milestone' => 7,
                'type' => 'Bug',
            ],
            $params->toArray()
        );
    }

    public function testOptionalsDropOutAtDefault(): void
    {
        $params = new CreateIssueParams(title: 't', body: '', assignees: [], labels: []);

        $this->assertSame(['title' => 't'], $params->toArray());
    }
}
