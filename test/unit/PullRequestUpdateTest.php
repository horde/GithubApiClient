<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\PullRequestUpdate;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PullRequestUpdate::class)]
class PullRequestUpdateTest extends TestCase
{
    public function testConstructorWithAllFields(): void
    {
        $update = new PullRequestUpdate(
            title: 'feat: new feature',
            body: 'This PR adds a new feature',
            base: 'main',
            state: 'open'
        );

        $this->assertSame('feat: new feature', $update->title);
        $this->assertSame('This PR adds a new feature', $update->body);
        $this->assertSame('main', $update->base);
        $this->assertSame('open', $update->state);
    }

    public function testConstructorWithOnlyTitle(): void
    {
        $update = new PullRequestUpdate(title: 'Updated title');

        $this->assertSame('Updated title', $update->title);
        $this->assertNull($update->body);
        $this->assertNull($update->base);
        $this->assertNull($update->state);
    }

    public function testConstructorWithNoFields(): void
    {
        $update = new PullRequestUpdate();

        $this->assertNull($update->title);
        $this->assertNull($update->body);
        $this->assertNull($update->base);
        $this->assertNull($update->state);
    }

    public function testToArrayIncludesOnlySetFields(): void
    {
        $update = new PullRequestUpdate(
            title: 'New title',
            state: 'closed'
        );

        $array = $update->toArray();

        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('state', $array);
        $this->assertArrayNotHasKey('body', $array);
        $this->assertArrayNotHasKey('base', $array);
        $this->assertSame('New title', $array['title']);
        $this->assertSame('closed', $array['state']);
    }

    public function testToArrayWithAllFields(): void
    {
        $update = new PullRequestUpdate(
            title: 'Title',
            body: 'Body',
            base: 'develop',
            state: 'open'
        );

        $array = $update->toArray();

        $this->assertCount(4, $array);
        $this->assertSame('Title', $array['title']);
        $this->assertSame('Body', $array['body']);
        $this->assertSame('develop', $array['base']);
        $this->assertSame('open', $array['state']);
    }

    public function testToArrayWithNoFields(): void
    {
        $update = new PullRequestUpdate();

        $array = $update->toArray();

        $this->assertEmpty($array);
        $this->assertIsArray($array);
    }

    public function testIsEmptyReturnsTrueWhenNoFieldsSet(): void
    {
        $update = new PullRequestUpdate();

        $this->assertTrue($update->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenTitleSet(): void
    {
        $update = new PullRequestUpdate(title: 'Test');

        $this->assertFalse($update->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenBodySet(): void
    {
        $update = new PullRequestUpdate(body: 'Test body');

        $this->assertFalse($update->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenBaseSet(): void
    {
        $update = new PullRequestUpdate(base: 'main');

        $this->assertFalse($update->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenStateSet(): void
    {
        $update = new PullRequestUpdate(state: 'closed');

        $this->assertFalse($update->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenMultipleFieldsSet(): void
    {
        $update = new PullRequestUpdate(title: 'Title', body: 'Body');

        $this->assertFalse($update->isEmpty());
    }
}
