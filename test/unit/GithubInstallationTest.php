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
#[CoversClass(GithubInstallation::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubInstallationTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $account = new GithubUser(
            login: 'octocat',
            id: 1,
            avatarUrl: 'https://avatars.githubusercontent.com/u/1',
            htmlUrl: 'https://github.com/octocat',
            type: 'User'
        );

        $installation = new GithubInstallation(
            id: 12345,
            account: $account,
            repositorySelection: 'all',
            createdAt: '2026-01-01T10:00:00Z',
            updatedAt: '2026-03-02T12:00:00Z'
        );

        $this->assertSame(12345, $installation->id);
        $this->assertSame($account, $installation->account);
        $this->assertSame('all', $installation->repositorySelection);
        $this->assertSame('2026-01-01T10:00:00Z', $installation->createdAt);
        $this->assertSame('2026-03-02T12:00:00Z', $installation->updatedAt);
    }

    public function testFromApiResponseWithCompleteData(): void
    {
        $data = json_decode(json_encode([
            'id' => 67890,
            'account' => [
                'login' => 'my-org',
                'id' => 999,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/999',
                'html_url' => 'https://github.com/my-org',
                'type' => 'Organization',
            ],
            'repository_selection' => 'selected',
            'created_at' => '2025-06-15T08:30:00Z',
            'updated_at' => '2026-02-20T14:45:00Z',
        ]));

        $installation = GithubInstallation::fromApiResponse($data);

        $this->assertSame(67890, $installation->id);
        $this->assertSame('my-org', $installation->account->login);
        $this->assertSame(999, $installation->account->id);
        $this->assertSame('Organization', $installation->account->type);
        $this->assertSame('selected', $installation->repositorySelection);
        $this->assertSame('2025-06-15T08:30:00Z', $installation->createdAt);
        $this->assertSame('2026-02-20T14:45:00Z', $installation->updatedAt);
    }

    public function testFromApiResponseWithMinimalData(): void
    {
        $data = json_decode(json_encode([
            'id' => 111,
        ]));

        $installation = GithubInstallation::fromApiResponse($data);

        $this->assertSame(111, $installation->id);
        $this->assertSame('', $installation->account->login);
        $this->assertSame(0, $installation->account->id);
        $this->assertSame('', $installation->repositorySelection);
        $this->assertSame('', $installation->createdAt);
        $this->assertSame('', $installation->updatedAt);
    }

    public function testToStringReturnsStringOfId(): void
    {
        $account = new GithubUser('test', 1, '', '', '');
        $installation = new GithubInstallation(
            id: 54321,
            account: $account,
            repositorySelection: 'all',
            createdAt: '2026-01-01T00:00:00Z',
            updatedAt: '2026-01-01T00:00:00Z'
        );

        $this->assertSame('54321', (string) $installation);
        $this->assertSame('54321', $installation->__toString());
    }

    public function testAccountGithubUserParsing(): void
    {
        $data = json_decode(json_encode([
            'id' => 123,
            'account' => [
                'login' => 'bot-account',
                'id' => 777,
                'avatar_url' => 'https://avatars.githubusercontent.com/u/777',
                'html_url' => 'https://github.com/bot-account',
                'type' => 'Bot',
            ],
            'repository_selection' => 'all',
            'created_at' => '2026-01-01T00:00:00Z',
            'updated_at' => '2026-02-01T00:00:00Z',
        ]));

        $installation = GithubInstallation::fromApiResponse($data);

        $this->assertInstanceOf(GithubUser::class, $installation->account);
        $this->assertSame('bot-account', $installation->account->login);
        $this->assertSame(777, $installation->account->id);
        $this->assertSame('Bot', $installation->account->type);
    }
}
