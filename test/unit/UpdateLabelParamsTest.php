<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\UpdateLabelParams;
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
#[CoversClass(UpdateLabelParams::class)]
class UpdateLabelParamsTest extends TestCase
{
    public function testAllNullReturnsEmpty(): void
    {
        $params = new UpdateLabelParams();

        $this->assertSame([], $params->toArray());
    }

    public function testPartialUpdateOnlyEmitsSetFields(): void
    {
        $params = new UpdateLabelParams(color: 'ff0000');

        $this->assertSame(['color' => 'ff0000'], $params->toArray());
    }

    public function testRenameUsesBodyName(): void
    {
        // Rename: URL key stays the lookup; body 'name' is the new name.
        $params = new UpdateLabelParams(name: 'critical-bug');

        $this->assertSame(['name' => 'critical-bug'], $params->toArray());
    }

    public function testEmptyDescriptionEmitsAsLiteralEmpty(): void
    {
        // null means "do not change", empty string means "set to empty".
        $params = new UpdateLabelParams(description: '');

        $this->assertSame(['description' => ''], $params->toArray());
    }
}
