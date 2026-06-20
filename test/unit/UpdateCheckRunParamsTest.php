<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use DateTimeImmutable;
use Horde\GithubApiClient\UpdateCheckRunParams;
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
#[CoversClass(UpdateCheckRunParams::class)]
class UpdateCheckRunParamsTest extends TestCase
{
    public function testAllEmptyToArrayReturnsEmpty(): void
    {
        $params = new UpdateCheckRunParams();
        $this->assertSame([], $params->toArray());
    }

    public function testPartialUpdateOnlyEmitsSetFields(): void
    {
        $params = new UpdateCheckRunParams(
            status: 'in_progress',
        );

        $this->assertSame(['status' => 'in_progress'], $params->toArray());
    }

    public function testCompletingPartialUpdate(): void
    {
        $completed = new DateTimeImmutable('2026-06-24T11:00:00+00:00');
        $params = new UpdateCheckRunParams(
            status: 'completed',
            conclusion: 'failure',
            completedAt: $completed,
            output: ['title' => 'Failed', 'summary' => '3 tests failed'],
        );

        $array = $params->toArray();

        $this->assertSame('completed', $array['status']);
        $this->assertSame('failure', $array['conclusion']);
        $this->assertSame($completed->format(DATE_ATOM), $array['completed_at']);
        $this->assertSame(['title' => 'Failed', 'summary' => '3 tests failed'], $array['output']);
        $this->assertArrayNotHasKey('name', $array);
        $this->assertArrayNotHasKey('head_sha', $array);
        $this->assertArrayNotHasKey('started_at', $array);
    }

    public function testSnakeCaseMappingForAllFields(): void
    {
        $started = new DateTimeImmutable('2026-06-24T10:00:00+00:00');
        $completed = new DateTimeImmutable('2026-06-24T10:05:00+00:00');
        $params = new UpdateCheckRunParams(
            name: 'Renamed',
            headSha: 'newsha',
            status: 'completed',
            conclusion: 'success',
            startedAt: $started,
            completedAt: $completed,
            detailsUrl: 'https://example.org/x',
            externalId: 'eid',
        );

        $array = $params->toArray();

        $this->assertArrayHasKey('head_sha', $array);
        $this->assertArrayHasKey('started_at', $array);
        $this->assertArrayHasKey('completed_at', $array);
        $this->assertArrayHasKey('details_url', $array);
        $this->assertArrayHasKey('external_id', $array);
        $this->assertArrayNotHasKey('headSha', $array);
        $this->assertArrayNotHasKey('detailsUrl', $array);
    }
}
