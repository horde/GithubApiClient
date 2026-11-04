<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Parameters for a repository settings update (PATCH /repos/{owner}/{repo}).
 *
 * Only the fields actually set are tried for the update, no need to send a full repository object.
 */
class RepositoryUpdate
{
    public function __construct(
        public readonly ?string $defaultBranch = null,
        public readonly ?string $description = null,
        public readonly ?string $homepage = null
    ) {}

    /**
     * @return array<string,string> The non-null fields keyed by their
     *                              GitHub REST property names.
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->defaultBranch !== null) {
            $data['default_branch'] = $this->defaultBranch;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->homepage !== null) {
            $data['homepage'] = $this->homepage;
        }

        return $data;
    }

    public function isEmpty(): bool
    {
        return $this->defaultBranch === null
            && $this->description === null
            && $this->homepage === null;
    }
}
