<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Iterator;
use Countable;

/**
 * Collection of GitHub reviews
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @implements Iterator<int, GithubReview>
 */
class GithubReviewList implements Iterator, Countable
{
    private int $position = 0;

    /**
     * @param array<GithubReview> $reviews
     */
    public function __construct(
        private readonly array $reviews
    ) {}

    public function current(): GithubReview
    {
        return $this->reviews[$this->position];
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
        return isset($this->reviews[$this->position]);
    }

    public function count(): int
    {
        return count($this->reviews);
    }

    /**
     * @return array<GithubReview>
     */
    public function toArray(): array
    {
        return $this->reviews;
    }
}
