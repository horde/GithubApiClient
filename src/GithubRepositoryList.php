<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use ArrayIterator;
use IteratorAggregate;
use Traversable;
use OutOfBoundsException;

/** @implements \IteratorAggregate<int, GithubRepository> */
class GithubRepositoryList implements IteratorAggregate
{
    /**
     * @var GithubRepository[]
     */
    private array $repositories = [];

    /**
     * @param iterable<mixed> $elements
     */
    public function __construct(iterable $elements = [])
    {
        foreach ($elements as $element) {
            if ($element instanceof GithubRepository) {
                $repositories[$element->getFullName()] = $element;
            } elseif (is_array($element)) {
                $repository = GithubRepository::fromApiArray($element);
            }
            // TODO: Exception on inappropriate type
        }
    }

    public function getRepositoryByFullName(string $fullName): GithubRepository
    {
        if (array_key_exists($fullName, $this->repositories)) {
            return $this->repositories[$fullName];
        }
        throw new OutOfBoundsException("Repository not found");
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->repositories);
    }
}
