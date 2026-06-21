<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Countable;
use Iterator;

/**
 * Collection of GitHub issue types
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @implements Iterator<int, GithubIssueType>
 */
class GithubIssueTypeList implements Iterator, Countable
{
    private int $position = 0;

    /**
     * @param array<GithubIssueType> $types
     */
    public function __construct(
        private readonly array $types = []
    ) {}

    public function current(): GithubIssueType
    {
        return $this->types[$this->position];
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
        return isset($this->types[$this->position]);
    }

    public function count(): int
    {
        return count($this->types);
    }

    /**
     * @return array<GithubIssueType>
     */
    public function toArray(): array
    {
        return $this->types;
    }
}
