<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubUser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[CoversClass(GithubUser::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubUserTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $user = new GithubUser(
            login: 'testuser',
            id: 12345,
            avatarUrl: 'https://avatar.example.com/testuser.png',
            htmlUrl: 'https://github.com/testuser',
            type: 'User'
        );

        $this->assertSame('testuser', $user->login);
        $this->assertSame(12345, $user->id);
        $this->assertSame('https://avatar.example.com/testuser.png', $user->avatarUrl);
        $this->assertSame('https://github.com/testuser', $user->htmlUrl);
        $this->assertSame('User', $user->type);
    }

    public function testConstructorWithDefaultType(): void
    {
        $user = new GithubUser(
            login: 'bot',
            id: 67890,
            avatarUrl: 'https://avatar.example.com/bot.png',
            htmlUrl: 'https://github.com/bot'
        );

        $this->assertSame('User', $user->type);
    }

    public function testToStringReturnsLogin(): void
    {
        $user = new GithubUser(
            login: 'testuser',
            id: 12345,
            avatarUrl: 'https://avatar.example.com/testuser.png',
            htmlUrl: 'https://github.com/testuser'
        );

        $this->assertSame('testuser', (string) $user);
    }

    public function testFromApiResponseWithCompleteData(): void
    {
        $data = (object) [
            'login' => 'apiuser',
            'id' => 99999,
            'avatar_url' => 'https://avatar.example.com/apiuser.png',
            'html_url' => 'https://github.com/apiuser',
            'type' => 'Bot'
        ];

        $user = GithubUser::fromApiResponse($data);

        $this->assertSame('apiuser', $user->login);
        $this->assertSame(99999, $user->id);
        $this->assertSame('https://avatar.example.com/apiuser.png', $user->avatarUrl);
        $this->assertSame('https://github.com/apiuser', $user->htmlUrl);
        $this->assertSame('Bot', $user->type);
    }

    public function testFromApiResponseWithMinimalData(): void
    {
        $data = (object) [];

        $user = GithubUser::fromApiResponse($data);

        $this->assertSame('', $user->login);
        $this->assertSame(0, $user->id);
        $this->assertSame('', $user->avatarUrl);
        $this->assertSame('', $user->htmlUrl);
        $this->assertSame('User', $user->type);
    }

    public function testFromApiResponseWithPartialData(): void
    {
        $data = (object) [
            'login' => 'partial',
            'id' => 54321
        ];

        $user = GithubUser::fromApiResponse($data);

        $this->assertSame('partial', $user->login);
        $this->assertSame(54321, $user->id);
        $this->assertSame('', $user->avatarUrl);
        $this->assertSame('', $user->htmlUrl);
        $this->assertSame('User', $user->type);
    }
}
