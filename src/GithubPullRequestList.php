<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use OutOfBoundsException;
use Stringable;

/** @implements \IteratorAggregate<int, GithubRepository> */
class GithubPullRequestList implements IteratorAggregate, Countable
{
    /**
     * @var GithubPullRequest[]
     */
    private array $prs = [];

    /**
     * @param iterable<GithubPullRequest|array<string|Stringable|int|null>> $elements
     */
    public function __construct(iterable $elements = [])
    {
        foreach ($elements as $element) {
            if ($element instanceof GithubPullRequest) {
                $this->prs[] = $element;
            }
            // TODO: Exception on inappropriate type
        }
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->prs);
    }

    public function count(): int
    {
        return count($this->prs);
    }
}
