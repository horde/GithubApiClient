<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\CreateInstallationAccessTokenParams;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

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
#[CoversClass(CreateInstallationAccessTokenParams::class)]
#[AllowMockObjectsWithoutExpectations]
class CreateInstallationAccessTokenParamsTest extends TestCase
{
    public function testConstructorWithNoParameters(): void
    {
        $params = new CreateInstallationAccessTokenParams();

        $this->assertSame([], $params->repositories);
        $this->assertSame([], $params->permissions);
    }

    public function testConstructorWithRepositoriesOnly(): void
    {
        $params = new CreateInstallationAccessTokenParams(
            repositories: ['repo1', 'repo2']
        );

        $this->assertSame(['repo1', 'repo2'], $params->repositories);
        $this->assertSame([], $params->permissions);
    }

    public function testConstructorWithPermissionsOnly(): void
    {
        $params = new CreateInstallationAccessTokenParams(
            permissions: ['contents' => 'read', 'issues' => 'write']
        );

        $this->assertSame([], $params->repositories);
        $this->assertSame(['contents' => 'read', 'issues' => 'write'], $params->permissions);
    }

    public function testConstructorWithBothRepositoriesAndPermissions(): void
    {
        $params = new CreateInstallationAccessTokenParams(
            repositories: ['my-repo'],
            permissions: ['pull_requests' => 'write', 'metadata' => 'read']
        );

        $this->assertSame(['my-repo'], $params->repositories);
        $this->assertSame(['pull_requests' => 'write', 'metadata' => 'read'], $params->permissions);
    }

    public function testToArrayExcludesEmptyArrays(): void
    {
        $params = new CreateInstallationAccessTokenParams();

        $array = $params->toArray();

        $this->assertSame([], $array);
        $this->assertArrayNotHasKey('repositories', $array);
        $this->assertArrayNotHasKey('permissions', $array);
    }

    public function testToArrayIncludesNonEmptyRepositories(): void
    {
        $params = new CreateInstallationAccessTokenParams(
            repositories: ['repo1', 'repo2', 'repo3']
        );

        $array = $params->toArray();

        $this->assertArrayHasKey('repositories', $array);
        $this->assertSame(['repo1', 'repo2', 'repo3'], $array['repositories']);
        $this->assertArrayNotHasKey('permissions', $array);
    }

    public function testToArrayIncludesNonEmptyPermissions(): void
    {
        $params = new CreateInstallationAccessTokenParams(
            permissions: ['contents' => 'write', 'issues' => 'read']
        );

        $array = $params->toArray();

        $this->assertArrayNotHasKey('repositories', $array);
        $this->assertArrayHasKey('permissions', $array);
        $this->assertSame(['contents' => 'write', 'issues' => 'read'], $array['permissions']);
    }

    public function testToArrayIncludesBothWhenNonEmpty(): void
    {
        $params = new CreateInstallationAccessTokenParams(
            repositories: ['test-repo'],
            permissions: ['metadata' => 'read']
        );

        $array = $params->toArray();

        $this->assertSame([
            'repositories' => ['test-repo'],
            'permissions' => ['metadata' => 'read'],
        ], $array);
    }
}
