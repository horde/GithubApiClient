<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

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
#[CoversClass(UpdateReleaseParams::class)]
class UpdateReleaseParamsTest extends TestCase
{
    public function testEmptyConstruction(): void
    {
        $params = new UpdateReleaseParams();

        $this->assertNull($params->tagName);
        $this->assertNull($params->name);
        $this->assertNull($params->body);
        $this->assertNull($params->targetCommitish);
        $this->assertNull($params->draft);
        $this->assertNull($params->prerelease);
    }

    public function testFullConstruction(): void
    {
        $params = new UpdateReleaseParams(
            tagName: 'v2.1.0',
            name: 'Updated Release',
            body: 'Updated release notes',
            draft: false,
            prerelease: true,
        );

        $this->assertSame('v2.1.0', $params->tagName);
        $this->assertSame('Updated Release', $params->name);
        $this->assertSame('Updated release notes', $params->body);
        $this->assertFalse($params->draft);
        $this->assertTrue($params->prerelease);
    }

    public function testIsEmptyWithNoParameters(): void
    {
        $params = new UpdateReleaseParams();

        $this->assertTrue($params->isEmpty());
    }

    public function testIsEmptyWithTagName(): void
    {
        $params = new UpdateReleaseParams(tagName: 'v1.0.0');

        $this->assertFalse($params->isEmpty());
    }

    public function testIsEmptyWithName(): void
    {
        $params = new UpdateReleaseParams(name: 'Release Name');

        $this->assertFalse($params->isEmpty());
    }

    public function testIsEmptyWithBody(): void
    {
        $params = new UpdateReleaseParams(body: 'Release body');

        $this->assertFalse($params->isEmpty());
    }

    public function testIsEmptyWithDraft(): void
    {
        $params = new UpdateReleaseParams(draft: true);

        $this->assertFalse($params->isEmpty());
    }

    public function testIsEmptyWithPrerelease(): void
    {
        $params = new UpdateReleaseParams(prerelease: false);

        $this->assertFalse($params->isEmpty());
    }

    public function testToArrayEmpty(): void
    {
        $params = new UpdateReleaseParams();

        $array = $params->toArray();

        $this->assertSame([], $array);
    }

    public function testToArrayWithTagName(): void
    {
        $params = new UpdateReleaseParams(tagName: 'v2.0.0');

        $array = $params->toArray();

        $this->assertSame(['tag_name' => 'v2.0.0'], $array);
    }

    public function testToArrayWithName(): void
    {
        $params = new UpdateReleaseParams(name: 'Version 2.0');

        $array = $params->toArray();

        $this->assertSame(['name' => 'Version 2.0'], $array);
    }

    public function testToArrayWithBody(): void
    {
        $params = new UpdateReleaseParams(body: 'New release notes');

        $array = $params->toArray();

        $this->assertSame(['body' => 'New release notes'], $array);
    }

    public function testToArrayWithDraftTrue(): void
    {
        $params = new UpdateReleaseParams(draft: true);

        $array = $params->toArray();

        $this->assertSame(['draft' => true], $array);
    }

    public function testToArrayWithDraftFalse(): void
    {
        $params = new UpdateReleaseParams(draft: false);

        $array = $params->toArray();

        $this->assertSame(['draft' => false], $array);
    }

    public function testToArrayWithPrereleaseFalse(): void
    {
        $params = new UpdateReleaseParams(prerelease: false);

        $array = $params->toArray();

        $this->assertSame(['prerelease' => false], $array);
    }

    public function testToArrayWithPrereleaseTrue(): void
    {
        $params = new UpdateReleaseParams(prerelease: true);

        $array = $params->toArray();

        $this->assertSame(['prerelease' => true], $array);
    }

    public function testToArrayFull(): void
    {
        $params = new UpdateReleaseParams(
            tagName: 'v3.0.0',
            name: 'Major Update',
            body: 'Complete rewrite',
            draft: false,
            prerelease: false,
        );

        $array = $params->toArray();

        $this->assertSame([
            'tag_name' => 'v3.0.0',
            'name' => 'Major Update',
            'body' => 'Complete rewrite',
            'draft' => false,
            'prerelease' => false,
        ], $array);
    }

    public function testToArrayWithEmptyStrings(): void
    {
        $params = new UpdateReleaseParams(
            name: '',
            body: '',
        );

        $array = $params->toArray();

        // Empty strings should still be included for updates (to clear fields)
        $this->assertSame([
            'name' => '',
            'body' => '',
        ], $array);
    }

    public function testPublishDraftRelease(): void
    {
        $params = new UpdateReleaseParams(draft: false);

        $this->assertFalse($params->isEmpty());
        $this->assertSame(['draft' => false], $params->toArray());
    }

    public function testMarkAsPrerelease(): void
    {
        $params = new UpdateReleaseParams(prerelease: true);

        $this->assertFalse($params->isEmpty());
        $this->assertSame(['prerelease' => true], $params->toArray());
    }
}
