<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use DateTimeImmutable;
use Horde\GithubApiClient\CreateCheckRunParams;
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
#[CoversClass(CreateCheckRunParams::class)]
class CreateCheckRunParamsTest extends TestCase
{
    public function testToArrayWithRequiredFieldsOnly(): void
    {
        $params = new CreateCheckRunParams(
            name: 'PHPUnit (php8.4-dev)',
            headSha: 'abc123'
        );

        $this->assertSame(
            [
                'name' => 'PHPUnit (php8.4-dev)',
                'head_sha' => 'abc123',
                'status' => 'completed',
            ],
            $params->toArray()
        );
    }

    public function testToArrayWithAllFields(): void
    {
        $started = new DateTimeImmutable('2026-06-24T10:00:00+00:00');
        $completed = new DateTimeImmutable('2026-06-24T10:05:00+00:00');
        $output = ['title' => 'Lane summary', 'summary' => 'All green'];

        $params = new CreateCheckRunParams(
            name: 'PHPUnit',
            headSha: 'deadbeef',
            status: 'completed',
            conclusion: 'success',
            startedAt: $started,
            completedAt: $completed,
            detailsUrl: 'https://example.org/runs/1',
            externalId: 'lane-php8.4-dev',
            output: $output,
        );

        $array = $params->toArray();

        $this->assertSame('PHPUnit', $array['name']);
        $this->assertSame('deadbeef', $array['head_sha']);
        $this->assertSame('completed', $array['status']);
        $this->assertSame('success', $array['conclusion']);
        $this->assertSame($started->format(DATE_ATOM), $array['started_at']);
        $this->assertSame($completed->format(DATE_ATOM), $array['completed_at']);
        $this->assertSame('https://example.org/runs/1', $array['details_url']);
        $this->assertSame('lane-php8.4-dev', $array['external_id']);
        $this->assertSame($output, $array['output']);
    }

    public function testToArrayOmitsOptionalsWhenDefault(): void
    {
        $params = new CreateCheckRunParams(name: 'Check', headSha: 'sha');
        $array = $params->toArray();

        $this->assertArrayNotHasKey('conclusion', $array);
        $this->assertArrayNotHasKey('started_at', $array);
        $this->assertArrayNotHasKey('completed_at', $array);
        $this->assertArrayNotHasKey('details_url', $array);
        $this->assertArrayNotHasKey('external_id', $array);
        $this->assertArrayNotHasKey('output', $array);
    }

    public function testDefaultStatusIsCompleted(): void
    {
        $params = new CreateCheckRunParams(name: 'Check', headSha: 'sha');
        $this->assertSame('completed', $params->status);
    }

    public function testStartedAtUsesIso8601(): void
    {
        $started = new DateTimeImmutable('2026-06-24T10:00:00+00:00');
        $params = new CreateCheckRunParams(
            name: 'Check',
            headSha: 'sha',
            startedAt: $started,
        );

        $this->assertSame('2026-06-24T10:00:00+00:00', $params->toArray()['started_at']);
    }
}
