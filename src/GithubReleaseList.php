<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Countable;
use Iterator;

/**
 * Collection of GitHub releases
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @implements Iterator<int, GithubRelease>
 */
class GithubReleaseList implements Iterator, Countable
{
    private int $position = 0;

    /**
     * @param array<GithubRelease> $releases
     */
    public function __construct(
        private readonly array $releases = []
    ) {}

    public function current(): GithubRelease
    {
        return $this->releases[$this->position];
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
        return isset($this->releases[$this->position]);
    }

    public function count(): int
    {
        return count($this->releases);
    }

    /**
     * @return array<GithubRelease>
     */
    public function toArray(): array
    {
        return $this->releases;
    }
}
