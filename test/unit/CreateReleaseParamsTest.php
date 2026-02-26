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
#[CoversClass(CreateReleaseParams::class)]
class CreateReleaseParamsTest extends TestCase
{
    public function testMinimalConstruction(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v1.0.0',
        );

        $this->assertSame('v1.0.0', $params->tagName);
        $this->assertSame('', $params->name);
        $this->assertSame('', $params->body);
        $this->assertSame('', $params->targetCommitish);
        $this->assertFalse($params->draft);
        $this->assertFalse($params->prerelease);
    }

    public function testFullConstruction(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v2.0.0',
            name: 'Release 2.0.0',
            body: 'Major release with breaking changes',
            targetCommitish: 'main',
            draft: true,
            prerelease: true,
        );

        $this->assertSame('v2.0.0', $params->tagName);
        $this->assertSame('Release 2.0.0', $params->name);
        $this->assertSame('Major release with breaking changes', $params->body);
        $this->assertSame('main', $params->targetCommitish);
        $this->assertTrue($params->draft);
        $this->assertTrue($params->prerelease);
    }

    public function testToArrayMinimal(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v1.0.0',
        );

        $array = $params->toArray();

        $this->assertSame([
            'tag_name' => 'v1.0.0',
            'draft' => false,
            'prerelease' => false,
        ], $array);
    }

    public function testToArrayFull(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v2.0.0',
            name: 'Release 2.0.0',
            body: 'Major release',
            targetCommitish: 'develop',
            draft: true,
            prerelease: false,
        );

        $array = $params->toArray();

        // Order matches toArray() implementation
        $this->assertArrayHasKey('tag_name', $array);
        $this->assertArrayHasKey('draft', $array);
        $this->assertArrayHasKey('prerelease', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('body', $array);
        $this->assertArrayHasKey('target_commitish', $array);

        $this->assertSame('v2.0.0', $array['tag_name']);
        $this->assertSame('Release 2.0.0', $array['name']);
        $this->assertSame('Major release', $array['body']);
        $this->assertSame('develop', $array['target_commitish']);
        $this->assertTrue($array['draft']);
        $this->assertFalse($array['prerelease']);
    }

    public function testToArrayWithEmptyStrings(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v1.0.0',
            name: '',
            body: '',
        );

        $array = $params->toArray();

        // Empty strings should not be included
        $this->assertSame([
            'tag_name' => 'v1.0.0',
            'draft' => false,
            'prerelease' => false,
        ], $array);
    }

    public function testToArrayWithOnlyName(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v1.0.0',
            name: 'Version 1.0.0',
        );

        $array = $params->toArray();

        $this->assertArrayHasKey('tag_name', $array);
        $this->assertArrayHasKey('draft', $array);
        $this->assertArrayHasKey('prerelease', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertSame('v1.0.0', $array['tag_name']);
        $this->assertSame('Version 1.0.0', $array['name']);
        $this->assertFalse($array['draft']);
        $this->assertFalse($array['prerelease']);
    }

    public function testToArrayWithOnlyBody(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v1.0.0',
            body: 'Release notes',
        );

        $array = $params->toArray();

        $this->assertArrayHasKey('tag_name', $array);
        $this->assertArrayHasKey('draft', $array);
        $this->assertArrayHasKey('prerelease', $array);
        $this->assertArrayHasKey('body', $array);
        $this->assertSame('v1.0.0', $array['tag_name']);
        $this->assertSame('Release notes', $array['body']);
        $this->assertFalse($array['draft']);
        $this->assertFalse($array['prerelease']);
    }

    public function testToArrayWithOnlyTargetCommitish(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v1.0.0',
            targetCommitish: 'abc123def',
        );

        $array = $params->toArray();

        $this->assertArrayHasKey('tag_name', $array);
        $this->assertArrayHasKey('draft', $array);
        $this->assertArrayHasKey('prerelease', $array);
        $this->assertArrayHasKey('target_commitish', $array);
        $this->assertSame('v1.0.0', $array['tag_name']);
        $this->assertSame('abc123def', $array['target_commitish']);
        $this->assertFalse($array['draft']);
        $this->assertFalse($array['prerelease']);
    }

    public function testDraftRelease(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v1.0.0-rc1',
            draft: true,
        );

        $array = $params->toArray();

        $this->assertTrue($array['draft']);
        $this->assertFalse($array['prerelease']);
    }

    public function testPrereleaseRelease(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v1.0.0-beta.1',
            prerelease: true,
        );

        $array = $params->toArray();

        $this->assertFalse($array['draft']);
        $this->assertTrue($array['prerelease']);
    }

    public function testDraftPrerelease(): void
    {
        $params = new CreateReleaseParams(
            tagName: 'v1.0.0-alpha.1',
            draft: true,
            prerelease: true,
        );

        $array = $params->toArray();

        $this->assertTrue($array['draft']);
        $this->assertTrue($array['prerelease']);
    }
}
