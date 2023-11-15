<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use ArrayIterator;
use IteratorAggregate;
use Traversable;
use OutOfBoundsException;
use Stringable;

/** @implements \IteratorAggregate<int, GithubRepository> */
class GithubRepositoryList implements IteratorAggregate
{
    /**
     * @var GithubRepository[]
     */
    private array $repositories = [];

    /**
     * @param iterable<GithubRepository|array<string|Stringable|int|null>> $elements
     */
    public function __construct(iterable $elements = [])
    {
        foreach ($elements as $element) {
            if ($element instanceof GithubRepository) {
                $this->repositories[$element->getFullName()] = $element;
            } elseif (
                is_array($element) &&
                array_key_exists('name', $element) &&
                array_key_exists('full_name', $element) &&
                array_key_exists('clone_url', $element)) {
                $repository = GithubRepository::fromApiArray($element);
                $this->repositories[$repository->getFullName()] = $repository;
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
