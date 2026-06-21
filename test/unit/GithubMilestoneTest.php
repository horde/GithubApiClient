<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubMilestone;
use Horde\GithubApiClient\GithubUser;
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
#[CoversClass(GithubMilestone::class)]
final class GithubMilestoneTest extends TestCase
{
    public function testFromApiResponseFull(): void
    {
        $creator = new stdClass();
        $creator->login = 'pm';
        $creator->id = 5;
        $creator->avatar_url = '';
        $creator->html_url = '';

        $data = new stdClass();
        $data->id = 10;
        $data->number = 3;
        $data->title = 'v2.0';
        $data->description = 'Major release';
        $data->state = 'open';
        $data->creator = $creator;
        $data->open_issues = 5;
        $data->closed_issues = 2;
        $data->html_url = 'https://github.com/o/r/milestone/3';
        $data->created_at = '2026-06-01T10:00:00Z';
        $data->updated_at = '2026-06-20T10:00:00Z';
        $data->closed_at = null;
        $data->due_on = '2026-07-31T23:59:59Z';

        $milestone = GithubMilestone::fromApiResponse($data);

        $this->assertSame(3, $milestone->number);
        $this->assertSame('v2.0', $milestone->title);
        $this->assertInstanceOf(GithubUser::class, $milestone->creator);
        $this->assertSame('pm', $milestone->creator->login);
        $this->assertSame(5, $milestone->openIssues);
        $this->assertSame(2, $milestone->closedIssues);
        $this->assertNull($milestone->closedAt);
        $this->assertSame('2026-07-31T23:59:59Z', $milestone->dueOn);
    }

    public function testFromApiResponseHandlesNullCreatorAndDueOn(): void
    {
        $data = new stdClass();
        $data->id = 1;
        $data->number = 1;
        $data->title = 'x';
        $data->description = '';
        $data->state = 'open';
        $data->creator = null;
        $data->open_issues = 0;
        $data->closed_issues = 0;
        $data->html_url = '';
        $data->created_at = '';
        $data->updated_at = '';
        $data->closed_at = null;
        $data->due_on = null;

        $milestone = GithubMilestone::fromApiResponse($data);

        $this->assertNull($milestone->creator);
        $this->assertNull($milestone->dueOn);
    }

    public function testStringableReturnsTitle(): void
    {
        $data = new stdClass();
        $data->number = 1;
        $data->title = 'Sprint 23';

        $milestone = GithubMilestone::fromApiResponse($data);

        $this->assertSame('Sprint 23', (string) $milestone);
    }
}
