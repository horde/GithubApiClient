<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Iterator;
use Countable;

/**
 * Collection of GitHub check runs
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @implements Iterator<int, GithubCheckRun>
 */
class GithubCheckRunList implements Iterator, Countable
{
    private int $position = 0;

    /**
     * @param array<GithubCheckRun> $checkRuns
     */
    public function __construct(
        private readonly array $checkRuns
    ) {}

    public function current(): GithubCheckRun
    {
        return $this->checkRuns[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->checkRuns[$this->position]);
    }

    public function count(): int
    {
        return count($this->checkRuns);
    }

    /**
     * @return array<GithubCheckRun>
     */
    public function toArray(): array
    {
        return $this->checkRuns;
    }
}
