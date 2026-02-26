<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;
use InvalidArgumentException;
use stdClass;

class GithubRepository
{
    public function __construct(
        private readonly string $name,
        private readonly string $fullName,
        private readonly string $description,
        private readonly string $cloneUrl
    ) {}
    public function getName(): string
    {
        return $this->name;
    }
    public function getFullName(): string
    {
        return $this->fullName;
    }
    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCloneUrl(): string
    {
        return $this->cloneUrl;
    }

    /**
     * @param non-empty-array<string|Stringable|int|null> $apiArray The Array form of the repository returned from Github JSON
     */
    public static function fromApiArray(array $apiArray): GithubRepository
    {
        if (self::isValidArrayRepresentation($apiArray)) {
            // TODO: Map more fields as needed
            return new GithubRepository(
                name: (string) $apiArray['name'],
                fullName: (string) $apiArray['full_name'],
                description: (string) ($apiArray['description'] ?? ''),
                cloneUrl: (string) $apiArray['clone_url'],
            );
        }
        throw new InvalidArgumentException();
    }

    public static function fromFullName(string $fullName, string $apiUrl = ''): GithubRepository
    {

        if (empty($fullName)) {
            throw new InvalidArgumentException('Full name cannot be empty');
        }
        $parts = explode('/', $fullName);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Invalid full name format');
        }
        return new GithubRepository(
            name: $parts[1],
            fullName: $fullName,
            description: '',
            cloneUrl: $apiUrl
        );
    }

    /**
     * @phpstan-assert-if-true array{'name': string|Stringable, 'full_name': string|Stringable, 'clone_url': string|Stringable, 'description': string|Stringable|null} $apiArray
     * @param array<mixed> $apiArray
     */
    public static function isValidArrayRepresentation(array $apiArray): bool
    {
        return array_key_exists('name', $apiArray)
        && array_key_exists('full_name', $apiArray)
        && array_key_exists('clone_url', $apiArray);
    }
}
