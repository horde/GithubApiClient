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
#[CoversClass(GithubInstallationList::class)]
#[AllowMockObjectsWithoutExpectations]
class GithubInstallationListTest extends TestCase
{
    public function testEmptyListCreation(): void
    {
        $list = new GithubInstallationList();

        $this->assertCount(0, $list);
        $this->assertSame([], $list->toArray());
    }

    public function testIterationWithForeach(): void
    {
        $account1 = new GithubUser('user1', 1, '', '', '');
        $account2 = new GithubUser('user2', 2, '', '', '');

        $installation1 = new GithubInstallation(100, $account1, 'all', '2026-01-01', '2026-01-01');
        $installation2 = new GithubInstallation(200, $account2, 'selected', '2026-01-02', '2026-01-02');

        $list = new GithubInstallationList([$installation1, $installation2]);

        $iterations = 0;
        $ids = [];

        foreach ($list as $installation) {
            $iterations++;
            $ids[] = $installation->id;
        }

        $this->assertSame(2, $iterations);
        $this->assertSame([100, 200], $ids);
    }

    public function testCountMethod(): void
    {
        $account = new GithubUser('test', 1, '', '', '');

        $installation1 = new GithubInstallation(1, $account, 'all', '', '');
        $installation2 = new GithubInstallation(2, $account, 'all', '', '');
        $installation3 = new GithubInstallation(3, $account, 'all', '', '');

        $list = new GithubInstallationList([$installation1, $installation2, $installation3]);

        $this->assertCount(3, $list);
        $this->assertSame(3, $list->count());
    }

    public function testToArrayMethod(): void
    {
        $account = new GithubUser('test', 1, '', '', '');

        $installation1 = new GithubInstallation(111, $account, 'all', '', '');
        $installation2 = new GithubInstallation(222, $account, 'selected', '', '');

        $list = new GithubInstallationList([$installation1, $installation2]);
        $array = $list->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertSame($installation1, $array[0]);
        $this->assertSame($installation2, $array[1]);
    }

    public function testMultipleInstallations(): void
    {
        $installations = [];
        for ($i = 1; $i <= 5; $i++) {
            $account = new GithubUser("user{$i}", $i, '', '', '');
            $installations[] = new GithubInstallation($i * 100, $account, 'all', '', '');
        }

        $list = new GithubInstallationList($installations);

        $this->assertCount(5, $list);

        $index = 0;
        foreach ($list as $key => $installation) {
            $this->assertSame($index, $key);
            $this->assertSame(($index + 1) * 100, $installation->id);
            $index++;
        }
    }

    public function testIteratorPositionTracking(): void
    {
        $account = new GithubUser('test', 1, '', '', '');
        $installation1 = new GithubInstallation(1, $account, 'all', '', '');
        $installation2 = new GithubInstallation(2, $account, 'all', '', '');

        $list = new GithubInstallationList([$installation1, $installation2]);

        // First iteration
        $list->rewind();
        $this->assertTrue($list->valid());
        $this->assertSame(0, $list->key());
        $this->assertSame($installation1, $list->current());

        // Move to next
        $list->next();
        $this->assertTrue($list->valid());
        $this->assertSame(1, $list->key());
        $this->assertSame($installation2, $list->current());

        // Move past end
        $list->next();
        $this->assertFalse($list->valid());

        // Rewind and iterate again
        $list->rewind();
        $this->assertTrue($list->valid());
        $this->assertSame(0, $list->key());
        $this->assertSame($installation1, $list->current());
    }
}
