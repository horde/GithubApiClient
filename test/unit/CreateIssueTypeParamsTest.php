<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\CreateIssueTypeParams;
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
#[CoversClass(CreateIssueTypeParams::class)]
final class CreateIssueTypeParamsTest extends TestCase
{
    public function testRequiredFieldsWithDefaults(): void
    {
        $params = new CreateIssueTypeParams(name: 'Bug');

        $this->assertSame(['name' => 'Bug', 'is_enabled' => true], $params->toArray());
    }

    public function testIsEnabledFalseEmits(): void
    {
        $params = new CreateIssueTypeParams(name: 'Deprecated', isEnabled: false);

        $array = $params->toArray();
        $this->assertArrayHasKey('is_enabled', $array);
        $this->assertFalse($array['is_enabled']);
    }

    public function testOptionalFieldsEmittedWhenSet(): void
    {
        $params = new CreateIssueTypeParams(
            name: 'Bug',
            description: 'A defect',
            color: 'red',
        );

        $this->assertSame(
            ['name' => 'Bug', 'is_enabled' => true, 'description' => 'A defect', 'color' => 'red'],
            $params->toArray()
        );
    }

    public function testSnakeCaseMapping(): void
    {
        $params = new CreateIssueTypeParams(name: 'x');
        $array = $params->toArray();

        $this->assertArrayHasKey('is_enabled', $array);
        $this->assertArrayNotHasKey('isEnabled', $array);
    }
}
