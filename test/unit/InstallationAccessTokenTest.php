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
#[CoversClass(InstallationAccessToken::class)]
#[AllowMockObjectsWithoutExpectations]
class InstallationAccessTokenTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $token = new InstallationAccessToken(
            token: 'ghs_test123',
            expiresAt: '2026-03-02T12:00:00Z',
            permissions: ['contents' => 'read', 'issues' => 'write'],
            repositorySelection: 'selected'
        );

        $this->assertSame('ghs_test123', $token->token);
        $this->assertSame('2026-03-02T12:00:00Z', $token->expiresAt);
        $this->assertSame(['contents' => 'read', 'issues' => 'write'], $token->permissions);
        $this->assertSame('selected', $token->repositorySelection);
    }

    public function testFromApiResponseWithCompleteData(): void
    {
        $data = json_decode(json_encode([
            'token' => 'ghs_16C7e42F292c6912E7710c838347Ae178B4a',
            'expires_at' => '2026-03-02T13:00:00Z',
            'permissions' => [
                'contents' => 'write',
                'issues' => 'read',
                'metadata' => 'read'
            ],
            'repository_selection' => 'all'
        ]));

        $token = InstallationAccessToken::fromApiResponse($data);

        $this->assertSame('ghs_16C7e42F292c6912E7710c838347Ae178B4a', $token->token);
        $this->assertSame('2026-03-02T13:00:00Z', $token->expiresAt);
        $this->assertSame([
            'contents' => 'write',
            'issues' => 'read',
            'metadata' => 'read'
        ], $token->permissions);
        $this->assertSame('all', $token->repositorySelection);
    }

    public function testFromApiResponseWithMinimalData(): void
    {
        $data = json_decode(json_encode([
            'token' => 'ghs_minimal',
            'expires_at' => '2026-03-02T14:00:00Z'
        ]));

        $token = InstallationAccessToken::fromApiResponse($data);

        $this->assertSame('ghs_minimal', $token->token);
        $this->assertSame('2026-03-02T14:00:00Z', $token->expiresAt);
        $this->assertSame([], $token->permissions);
        $this->assertSame('', $token->repositorySelection);
    }

    public function testPermissionsArrayHandling(): void
    {
        $data = json_decode(json_encode([
            'token' => 'ghs_test',
            'expires_at' => '2026-03-02T15:00:00Z',
            'permissions' => [
                'pull_requests' => 'write',
                'checks' => 'read'
            ],
            'repository_selection' => 'selected'
        ]));

        $token = InstallationAccessToken::fromApiResponse($data);

        $this->assertIsArray($token->permissions);
        $this->assertCount(2, $token->permissions);
        $this->assertArrayHasKey('pull_requests', $token->permissions);
        $this->assertArrayHasKey('checks', $token->permissions);
    }

    public function testRepositorySelectionValues(): void
    {
        // Test 'all' selection
        $dataAll = json_decode(json_encode([
            'token' => 'ghs_all',
            'expires_at' => '2026-03-02T16:00:00Z',
            'repository_selection' => 'all'
        ]));

        $tokenAll = InstallationAccessToken::fromApiResponse($dataAll);
        $this->assertSame('all', $tokenAll->repositorySelection);

        // Test 'selected' selection
        $dataSelected = json_decode(json_encode([
            'token' => 'ghs_selected',
            'expires_at' => '2026-03-02T17:00:00Z',
            'repository_selection' => 'selected'
        ]));

        $tokenSelected = InstallationAccessToken::fromApiResponse($dataSelected);
        $this->assertSame('selected', $tokenSelected->repositorySelection);
    }

    public function testDoesNotImplementStringable(): void
    {
        $token = new InstallationAccessToken(
            token: 'ghs_secret',
            expiresAt: '2026-03-02T18:00:00Z',
            permissions: [],
            repositorySelection: 'all'
        );

        // Verify token doesn't implement Stringable (for security)
        $reflection = new \ReflectionClass($token);
        $interfaces = $reflection->getInterfaceNames();

        $this->assertNotContains('Stringable', $interfaces);
    }
}
