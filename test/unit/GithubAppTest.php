<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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
#[CoversClass(GithubApp::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubAppTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $owner = new GithubUser(
            login: 'app-owner',
            id: 123,
            avatarUrl: 'https://avatars.githubusercontent.com/u/123',
            htmlUrl: 'https://github.com/app-owner',
            type: 'User'
        );

        $app = new GithubApp(
            id: 456,
            slug: 'my-awesome-app',
            name: 'My Awesome App',
            owner: $owner,
            createdAt: '2025-01-15T10:00:00Z',
            updatedAt: '2026-02-20T15:30:00Z'
        );

        $this->assertSame(456, $app->id);
        $this->assertSame('my-awesome-app', $app->slug);
        $this->assertSame('My Awesome App', $app->name);
        $this->assertSame($owner, $app->owner);
        $this->assertSame('2025-01-15T10:00:00Z', $app->createdAt);
        $this->assertSame('2026-02-20T15:30:00Z', $app->updatedAt);
    }

    public function testFromApiResponseWithCompleteData(): void
    {
        $data = json_decode(json_encode([
            'id' => 789,
            'slug' => 'ci-bot',
            'name' => 'CI Bot',
            'owner' => [
                'login' => 'org-name',
                'id' => 999,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/999',
                'html_url' => 'https://github.com/org-name',
                'type' => 'Organization'
            ],
            'created_at' => '2024-06-01T00:00:00Z',
            'updated_at' => '2026-03-01T12:00:00Z'
        ]));

        $app = GithubApp::fromApiResponse($data);

        $this->assertSame(789, $app->id);
        $this->assertSame('ci-bot', $app->slug);
        $this->assertSame('CI Bot', $app->name);
        $this->assertSame('org-name', $app->owner->login);
        $this->assertSame(999, $app->owner->id);
        $this->assertSame('Organization', $app->owner->type);
        $this->assertSame('2024-06-01T00:00:00Z', $app->createdAt);
        $this->assertSame('2026-03-01T12:00:00Z', $app->updatedAt);
    }

    public function testFromApiResponseWithMinimalData(): void
    {
        $data = json_decode(json_encode([
            'id' => 1
        ]));

        $app = GithubApp::fromApiResponse($data);

        $this->assertSame(1, $app->id);
        $this->assertSame('', $app->slug);
        $this->assertSame('', $app->name);
        $this->assertSame('', $app->owner->login);
        $this->assertSame(0, $app->owner->id);
        $this->assertSame('', $app->createdAt);
        $this->assertSame('', $app->updatedAt);
    }

    public function testToStringReturnsSlug(): void
    {
        $owner = new GithubUser('test', 1, '', '', '');
        $app = new GithubApp(
            id: 100,
            slug: 'test-app-slug',
            name: 'Test App',
            owner: $owner,
            createdAt: '2026-01-01T00:00:00Z',
            updatedAt: '2026-01-01T00:00:00Z'
        );

        $this->assertSame('test-app-slug', (string) $app);
        $this->assertSame('test-app-slug', $app->__toString());
    }

    public function testOwnerGithubUserParsing(): void
    {
        $data = json_decode(json_encode([
            'id' => 222,
            'slug' => 'automation-app',
            'name' => 'Automation App',
            'owner' => [
                'login' => 'bot-user',
                'id' => 333,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/333',
                'html_url' => 'https://github.com/bot-user',
                'type' => 'Bot'
            ],
            'created_at' => '2025-12-01T00:00:00Z',
            'updated_at' => '2026-01-15T00:00:00Z'
        ]));

        $app = GithubApp::fromApiResponse($data);

        $this->assertInstanceOf(GithubUser::class, $app->owner);
        $this->assertSame('bot-user', $app->owner->login);
        $this->assertSame(333, $app->owner->id);
        $this->assertSame('Bot', $app->owner->type);
    }
}
