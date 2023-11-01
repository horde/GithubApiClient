<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

class GithubRepository
{
    public function __construct(
        private readonly string $name,
        private readonly string $fullName,
        private readonly string $description,
        private readonly string $cloneUrl
    ) {

    }
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
     * @param array<mixed> $apiArray
     */
    public static function fromApiArray(array $apiArray): GithubRepository
    {
        // TODO: Map more fields as needed
        return new GithubRepository(
            name: (string) $apiArray['name'],
            fullName:  (string) $apiArray['full_name'],
            description:  (string) ($apiArray['description'] ?? ''),
            cloneUrl:  (string)  $apiArray['clone_url'],
        );
    }
}
