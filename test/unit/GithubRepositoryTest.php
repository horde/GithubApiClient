<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[CoversClass(GithubRepository::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubRepositoryTest extends TestCase
{
    public function testFromFullNameParsesOwnerAndName(): void
    {
        $repo = GithubRepository::fromFullName('horde/githubapiclient');

        $this->assertSame('horde', $repo->owner);
        $this->assertSame('githubapiclient', $repo->name);
        $this->assertSame('horde/githubapiclient', $repo->getFullName());
    }

    public function testFromFullNameWithDifferentOwner(): void
    {
        $repo = GithubRepository::fromFullName('octocat/Hello-World');

        $this->assertSame('octocat', $repo->owner);
        $this->assertSame('Hello-World', $repo->name);
        $this->assertSame('octocat/Hello-World', $repo->getFullName());
    }

    public function testFromFullNameWithHyphenatedNames(): void
    {
        $repo = GithubRepository::fromFullName('my-org/my-repo-name');

        $this->assertSame('my-org', $repo->owner);
        $this->assertSame('my-repo-name', $repo->name);
        $this->assertSame('my-org/my-repo-name', $repo->getFullName());
    }

    public function testFromFullNameThrowsOnEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Full name cannot be empty');

        GithubRepository::fromFullName('');
    }

    public function testFromFullNameThrowsOnInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid full name format');

        GithubRepository::fromFullName('invalid-no-slash');
    }

    public function testFromFullNameThrowsOnTooManySlashes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid full name format');

        GithubRepository::fromFullName('owner/repo/extra');
    }

    public function testFromApiArrayParsesOwnerFromFullName(): void
    {
        $data = [
            'name' => 'components',
            'full_name' => 'horde/components',
            'description' => 'Component management tool',
            'clone_url' => 'https://github.com/horde/components.git'
        ];

        $repo = GithubRepository::fromApiArray($data);

        $this->assertSame('horde', $repo->owner);
        $this->assertSame('components', $repo->name);
        $this->assertSame('horde/components', $repo->getFullName());
        $this->assertSame('Component management tool', $repo->getDescription());
    }

    public function testConstructorParsesOwnerFromFullName(): void
    {
        $repo = new GithubRepository(
            name: 'test-repo',
            fullName: 'test-owner/test-repo',
            description: 'Test',
            cloneUrl: 'https://github.com/test-owner/test-repo.git'
        );

        $this->assertSame('test-owner', $repo->owner);
        $this->assertSame('test-repo', $repo->name);
    }

    public function testOwnerAndNameArePubliclyAccessible(): void
    {
        $repo = GithubRepository::fromFullName('microsoft/vscode');

        // Test that owner and name can be accessed as public properties
        // This is required for request factories to build API URLs
        $owner = $repo->owner;
        $name = $repo->name;

        $this->assertSame('microsoft', $owner);
        $this->assertSame('vscode', $name);
    }

    public function testOwnerAndNameAreReadonly(): void
    {
        $repo = GithubRepository::fromFullName('github/gitignore');

        // Verify properties are readonly (this will be caught by PHP at runtime)
        $reflection = new \ReflectionProperty(GithubRepository::class, 'owner');
        $this->assertTrue($reflection->isReadOnly());

        $reflection = new \ReflectionProperty(GithubRepository::class, 'name');
        $this->assertTrue($reflection->isReadOnly());
    }
}
