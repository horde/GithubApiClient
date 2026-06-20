<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\TokenScopes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TokenScopes::class)]
final class TokenScopesTest extends TestCase
{
    public function testConstructorFiltersDuplicatesAndNonStrings(): void
    {
        $scopes = new TokenScopes(['repo', 'repo', 'read:org', 123, null, 'admin:org']);

        $this->assertSame(['repo', 'read:org', 'admin:org'], $scopes->toArray());
        $this->assertSame(3, $scopes->count());
    }

    public function testFromHeaderParsesCommaSeparatedList(): void
    {
        $scopes = TokenScopes::fromHeader('repo, read:org, admin:org');

        $this->assertSame(['repo', 'read:org', 'admin:org'], $scopes->toArray());
    }

    public function testFromHeaderHandlesEmptyString(): void
    {
        $scopes = TokenScopes::fromHeader('');

        $this->assertTrue($scopes->isEmpty());
        $this->assertSame([], $scopes->toArray());
    }

    public function testFromHeaderHandlesWhitespaceOnly(): void
    {
        $scopes = TokenScopes::fromHeader('   ');

        $this->assertTrue($scopes->isEmpty());
    }

    public function testHasReturnsTrueForExistingScope(): void
    {
        $scopes = new TokenScopes(['repo', 'read:org']);

        $this->assertTrue($scopes->has('repo'));
        $this->assertTrue($scopes->has('read:org'));
    }

    public function testHasReturnsFalseForNonExistingScope(): void
    {
        $scopes = new TokenScopes(['repo']);

        $this->assertFalse($scopes->has('admin:org'));
    }

    public function testHasAnyReturnsTrueIfAtLeastOneScopeExists(): void
    {
        $scopes = new TokenScopes(['repo', 'read:org']);

        $this->assertTrue($scopes->hasAny(['repo', 'admin:org']));
        $this->assertTrue($scopes->hasAny(['admin:org', 'read:org']));
    }

    public function testHasAnyReturnsFalseIfNoScopeExists(): void
    {
        $scopes = new TokenScopes(['repo']);

        $this->assertFalse($scopes->hasAny(['admin:org', 'write:org']));
    }

    public function testHasAllReturnsTrueIfAllScopesExist(): void
    {
        $scopes = new TokenScopes(['repo', 'read:org', 'admin:org']);

        $this->assertTrue($scopes->hasAll(['repo', 'read:org']));
    }

    public function testHasAllReturnsFalseIfAnyScopeMissing(): void
    {
        $scopes = new TokenScopes(['repo']);

        $this->assertFalse($scopes->hasAll(['repo', 'admin:org']));
    }

    public function testIsEmptyReturnsTrueForNoScopes(): void
    {
        $scopes = new TokenScopes([]);

        $this->assertTrue($scopes->isEmpty());
    }

    public function testIsEmptyReturnsFalseForScopes(): void
    {
        $scopes = new TokenScopes(['repo']);

        $this->assertFalse($scopes->isEmpty());
    }

    public function testCanReadRepositoriesWithRepoScope(): void
    {
        $scopes = new TokenScopes(['repo']);

        $this->assertTrue($scopes->canReadRepositories());
    }

    public function testCanReadRepositoriesWithPublicRepoScope(): void
    {
        $scopes = new TokenScopes(['public_repo']);

        $this->assertTrue($scopes->canReadRepositories());
    }

    public function testCanReadRepositoriesReturnsFalseWithoutScopes(): void
    {
        $scopes = new TokenScopes(['admin:org']);

        $this->assertFalse($scopes->canReadRepositories());
    }

    public function testCanWriteRepositoriesRequiresFullRepoScope(): void
    {
        $scopesWithRepo = new TokenScopes(['repo']);
        $scopesWithPublic = new TokenScopes(['public_repo']);

        $this->assertTrue($scopesWithRepo->canWriteRepositories());
        $this->assertFalse($scopesWithPublic->canWriteRepositories());
    }

    public function testCanReadOrganizationsWithVariousScopes(): void
    {
        $this->assertTrue((new TokenScopes(['read:org']))->canReadOrganizations());
        $this->assertTrue((new TokenScopes(['admin:org']))->canReadOrganizations());
        $this->assertTrue((new TokenScopes(['write:org']))->canReadOrganizations());
        $this->assertFalse((new TokenScopes(['repo']))->canReadOrganizations());
    }

    public function testToStringReturnsCommaSeparatedList(): void
    {
        $scopes = new TokenScopes(['repo', 'read:org', 'admin:org']);

        $this->assertSame('repo, read:org, admin:org', $scopes->toString());
    }

    public function testCountReturnsCorrectNumber(): void
    {
        $scopes = new TokenScopes(['repo', 'read:org', 'admin:org']);

        $this->assertSame(3, $scopes->count());
    }
}
