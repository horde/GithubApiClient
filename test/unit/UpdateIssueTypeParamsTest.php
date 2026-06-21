<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\UpdateIssueTypeParams;
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
#[CoversClass(UpdateIssueTypeParams::class)]
final class UpdateIssueTypeParamsTest extends TestCase
{
    public function testAllNullReturnsEmpty(): void
    {
        $this->assertSame([], (new UpdateIssueTypeParams())->toArray());
    }

    public function testPartialUpdate(): void
    {
        $params = new UpdateIssueTypeParams(color: 'green');

        $this->assertSame(['color' => 'green'], $params->toArray());
    }

    public function testIsEnabledFalseEmits(): void
    {
        // Setting isEnabled to false is meaningful — distinct from null/untouched.
        $params = new UpdateIssueTypeParams(isEnabled: false);

        $array = $params->toArray();
        $this->assertArrayHasKey('is_enabled', $array);
        $this->assertFalse($array['is_enabled']);
    }
}
