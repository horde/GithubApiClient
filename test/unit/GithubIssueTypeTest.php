<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubIssueType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

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
#[CoversClass(GithubIssueType::class)]
final class GithubIssueTypeTest extends TestCase
{
    public function testFromApiResponseFull(): void
    {
        $data = new stdClass();
        $data->id = 9;
        $data->node_id = 'IT_kwAB';
        $data->name = 'Bug';
        $data->description = 'A defect';
        $data->color = 'red';
        $data->is_enabled = true;
        $data->created_at = '2026-06-01T10:00:00Z';
        $data->updated_at = '2026-06-20T10:00:00Z';

        $type = GithubIssueType::fromApiResponse($data);

        $this->assertSame(9, $type->id);
        $this->assertSame('Bug', $type->name);
        $this->assertSame('A defect', $type->description);
        $this->assertSame('red', $type->color);
        $this->assertTrue($type->isEnabled);
        $this->assertSame('IT_kwAB', $type->nodeId);
    }

    public function testFromApiResponseNullableFields(): void
    {
        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Task';
        $data->description = null;
        $data->color = null;
        $data->is_enabled = false;
        $data->created_at = '';
        $data->updated_at = '';

        $type = GithubIssueType::fromApiResponse($data);

        $this->assertNull($type->description);
        $this->assertNull($type->color);
        $this->assertFalse($type->isEnabled);
    }

    public function testStringableReturnsName(): void
    {
        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Feature';

        $this->assertSame('Feature', (string) GithubIssueType::fromApiResponse($data));
    }
}
