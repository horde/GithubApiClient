<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubIssueFactory;
use Horde\GithubApiClient\GithubLabel;
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
#[CoversClass(GithubIssueFactory::class)]
final class GithubIssueTest extends TestCase
{
    private function user(string $login = 'octocat'): stdClass
    {
        $u = new stdClass();
        $u->login = $login;
        $u->id = 1;
        $u->avatar_url = 'https://example/u.png';
        $u->html_url = 'https://github.com/' . $login;
        return $u;
    }

    public function testFromApiResponseRegularIssue(): void
    {
        $data = new stdClass();
        $data->id = 999;
        $data->number = 42;
        $data->title = 'Crash on launch';
        $data->body = 'Steps to reproduce…';
        $data->state = 'open';
        $data->state_reason = null;
        $data->html_url = 'https://github.com/o/r/issues/42';
        $data->url = 'https://api.github.com/repos/o/r/issues/42';
        $data->user = $this->user('reporter');
        $data->labels = [];
        $data->assignees = [];
        $data->milestone = null;
        $data->type = null;
        $data->comments = 3;
        $data->created_at = '2026-06-24T10:00:00Z';
        $data->updated_at = '2026-06-24T10:01:00Z';
        $data->closed_at = null;

        $issue = (new GithubIssueFactory())->createFromApiResponse($data);

        $this->assertSame(42, $issue->number);
        $this->assertSame('Crash on launch', $issue->title);
        $this->assertSame('reporter', $issue->author->login);
        $this->assertFalse($issue->isPullRequest);
        $this->assertNull($issue->milestone);
        $this->assertNull($issue->type);
        $this->assertSame([], $issue->labels);
        $this->assertSame([], $issue->assignees);
        $this->assertSame([], $issue->fieldValues);
    }

    public function testFromApiResponseDetectsPullRequest(): void
    {
        $data = new stdClass();
        $data->number = 7;
        $data->title = 'PR title';
        $data->body = '';
        $data->state = 'open';
        $data->html_url = '';
        $data->url = '';
        $data->user = $this->user();
        $data->pull_request = new stdClass();
        $data->pull_request->url = 'https://api.github.com/repos/o/r/pulls/7';

        $issue = (new GithubIssueFactory())->createFromApiResponse($data);

        $this->assertTrue($issue->isPullRequest);
    }

    public function testFromApiResponseDecodesNestedLabelsAndAssignees(): void
    {
        $label = new stdClass();
        $label->name = 'bug';
        $label->color = 'd73a4a';
        $label->description = 'Something broken';

        $data = new stdClass();
        $data->number = 1;
        $data->title = 't';
        $data->body = '';
        $data->state = 'open';
        $data->html_url = '';
        $data->url = '';
        $data->user = $this->user();
        $data->labels = [$label];
        $data->assignees = [$this->user('alice'), $this->user('bob')];

        $issue = (new GithubIssueFactory())->createFromApiResponse($data);

        $this->assertCount(1, $issue->labels);
        $this->assertInstanceOf(GithubLabel::class, $issue->labels[0]);
        $this->assertSame('bug', $issue->labels[0]->name);
        $this->assertCount(2, $issue->assignees);
        $this->assertSame('alice', $issue->assignees[0]->login);
        $this->assertSame('bob', $issue->assignees[1]->login);
    }

    public function testFromApiResponseDecodesMilestoneAndType(): void
    {
        $milestone = new stdClass();
        $milestone->id = 1;
        $milestone->number = 3;
        $milestone->title = 'v2';
        $milestone->state = 'open';
        $milestone->description = '';
        $milestone->open_issues = 5;
        $milestone->closed_issues = 2;
        $milestone->html_url = '';
        $milestone->created_at = '';
        $milestone->updated_at = '';

        $type = new stdClass();
        $type->id = 9;
        $type->name = 'Bug';
        $type->description = 'A bug';
        $type->color = 'red';
        $type->is_enabled = true;
        $type->created_at = '';
        $type->updated_at = '';

        $data = new stdClass();
        $data->number = 1;
        $data->title = 't';
        $data->body = '';
        $data->state = 'open';
        $data->html_url = '';
        $data->url = '';
        $data->user = $this->user();
        $data->milestone = $milestone;
        $data->type = $type;

        $issue = (new GithubIssueFactory())->createFromApiResponse($data);

        $this->assertNotNull($issue->milestone);
        $this->assertSame('v2', $issue->milestone->title);
        $this->assertNotNull($issue->type);
        $this->assertSame('Bug', $issue->type->name);
    }

    public function testFromApiResponseParsesFieldValues(): void
    {
        $field1 = new stdClass();
        $field1->name = 'Priority';
        $field1->value = 'P1';
        $field2 = new stdClass();
        $field2->name = 'Severity';
        $field2->value = 3;

        $data = new stdClass();
        $data->number = 1;
        $data->title = 't';
        $data->body = '';
        $data->state = 'open';
        $data->html_url = '';
        $data->url = '';
        $data->user = $this->user();
        $data->issue_field_values = [$field1, $field2];

        $issue = (new GithubIssueFactory())->createFromApiResponse($data);

        $this->assertSame(['Priority' => 'P1', 'Severity' => 3], $issue->fieldValues);
    }

    public function testFromApiResponseHandlesMissingFieldsCleanly(): void
    {
        // Bare-minimum payload — every nullable falls back without throwing.
        $data = new stdClass();
        $data->number = 1;
        $data->title = '';
        $data->user = $this->user();

        $issue = (new GithubIssueFactory())->createFromApiResponse($data);

        $this->assertSame(1, $issue->number);
        $this->assertSame('open', $issue->state);
        $this->assertNull($issue->milestone);
        $this->assertNull($issue->type);
        $this->assertSame([], $issue->labels);
        $this->assertFalse($issue->isPullRequest);
    }

    public function testStringableFormat(): void
    {
        $data = new stdClass();
        $data->number = 42;
        $data->title = 'Crash on launch';
        $data->user = $this->user();

        $issue = (new GithubIssueFactory())->createFromApiResponse($data);

        $this->assertSame('#42 Crash on launch', (string) $issue);
    }
}
