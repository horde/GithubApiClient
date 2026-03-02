<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubApiConfig;
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
#[CoversClass(GithubApiConfig::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubApiConfigTest extends TestCase
{
    public function testDefaultConstructorValues(): void
    {
        $config = new GithubApiConfig();

        $this->assertSame('https://api.github.com', $config->endpoint);
        $this->assertSame('', $config->accessToken);
        $this->assertSame('2022-11-28', $config->apiVersion);
        $this->assertSame('', $config->jwt);
    }

    public function testWithAccessTokenOnly(): void
    {
        $config = new GithubApiConfig(accessToken: 'ghp_test123');

        $this->assertSame('https://api.github.com', $config->endpoint);
        $this->assertSame('ghp_test123', $config->accessToken);
        $this->assertSame('2022-11-28', $config->apiVersion);
        $this->assertSame('', $config->jwt);
    }

    public function testWithJwtOnly(): void
    {
        $config = new GithubApiConfig(jwt: 'eyJhbGc.eyJpc3M.signature');

        $this->assertSame('https://api.github.com', $config->endpoint);
        $this->assertSame('', $config->accessToken);
        $this->assertSame('2022-11-28', $config->apiVersion);
        $this->assertSame('eyJhbGc.eyJpc3M.signature', $config->jwt);
    }

    public function testWithBothAccessTokenAndJwt(): void
    {
        $config = new GithubApiConfig(
            accessToken: 'ghp_test123',
            jwt: 'eyJhbGc.eyJpc3M.signature'
        );

        $this->assertSame('https://api.github.com', $config->endpoint);
        $this->assertSame('ghp_test123', $config->accessToken);
        $this->assertSame('2022-11-28', $config->apiVersion);
        $this->assertSame('eyJhbGc.eyJpc3M.signature', $config->jwt);
    }

    public function testWithCustomEndpointAndApiVersion(): void
    {
        $config = new GithubApiConfig(
            endpoint: 'https://github.example.com/api/v3',
            accessToken: 'token123',
            apiVersion: '2023-01-01',
            jwt: 'jwt123'
        );

        $this->assertSame('https://github.example.com/api/v3', $config->endpoint);
        $this->assertSame('token123', $config->accessToken);
        $this->assertSame('2023-01-01', $config->apiVersion);
        $this->assertSame('jwt123', $config->jwt);
    }

    public function testPropertiesAreReadonly(): void
    {
        $config = new GithubApiConfig();

        $reflection = new \ReflectionClass($config);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }
}
