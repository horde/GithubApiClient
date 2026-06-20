<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\CreateLabelParams;
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
#[CoversClass(CreateLabelParams::class)]
class CreateLabelParamsTest extends TestCase
{
    public function testRequiredFieldsAlwaysEmit(): void
    {
        $params = new CreateLabelParams(name: 'bug', color: 'd73a4a');

        $this->assertSame(['name' => 'bug', 'color' => 'd73a4a'], $params->toArray());
    }

    public function testDescriptionEmittedWhenNonEmpty(): void
    {
        $params = new CreateLabelParams(name: 'bug', color: 'd73a4a', description: 'Something broken');

        $this->assertSame(
            ['name' => 'bug', 'color' => 'd73a4a', 'description' => 'Something broken'],
            $params->toArray()
        );
    }

    public function testDescriptionOmittedWhenEmpty(): void
    {
        $params = new CreateLabelParams(name: 'bug', color: 'd73a4a', description: '');

        $this->assertArrayNotHasKey('description', $params->toArray());
    }

    public function testColorShapeNotValidated(): void
    {
        // The library does not validate the hex format; GitHub rejects.
        $params = new CreateLabelParams(name: 'x', color: 'not-hex');

        $this->assertSame('not-hex', $params->toArray()['color']);
    }
}
