<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Countable;
use Iterator;

/**
 * Collection of GitHub App installations
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class GithubInstallationList implements Iterator, Countable
{
    private int $position = 0;

    /**
     * @param array<GithubInstallation> $installations
     */
    public function __construct(
        private array $installations = []
    ) {}

    public function current(): GithubInstallation
    {
        return $this->installations[$this->position];
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
        return isset($this->installations[$this->position]);
    }

    public function count(): int
    {
        return count($this->installations);
    }

    /**
     * Get all installations as array
     *
     * @return array<GithubInstallation>
     */
    public function toArray(): array
    {
        return $this->installations;
    }
}
