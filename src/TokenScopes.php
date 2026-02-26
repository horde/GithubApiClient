<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Represents GitHub token OAuth scopes/permissions
 */
class TokenScopes
{
    /** @var array<string> */
    private readonly array $scopes;

    /**
     * @param array<string> $scopes
     */
    public function __construct(array $scopes)
    {
        $this->scopes = array_values(array_unique(array_filter($scopes, 'is_string')));
    }

    /**
     * Create from X-OAuth-Scopes header value
     *
     * @param string $headerValue Comma-separated scope list from X-OAuth-Scopes header
     * @return self
     */
    public static function fromHeader(string $headerValue): self
    {
        if (empty(trim($headerValue))) {
            return new self([]);
        }

        $scopes = array_map('trim', explode(',', $headerValue));
        return new self($scopes);
    }

    /**
     * Get all scopes as array
     *
     * @return array<string>
     */
    public function toArray(): array
    {
        return $this->scopes;
    }

    /**
     * Check if a specific scope is granted
     *
     * @param string $scope
     * @return bool
     */
    public function has(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    /**
     * Check if any of the given scopes are granted
     *
     * @param array<string> $scopes
     * @return bool
     */
    public function hasAny(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($this->has($scope)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if all given scopes are granted
     *
     * @param array<string> $scopes
     * @return bool
     */
    public function hasAll(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (!$this->has($scope)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get number of scopes
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->scopes);
    }

    /**
     * Check if token has no scopes (empty)
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->scopes);
    }

    /**
     * Check if token has read access to repositories
     *
     * @return bool
     */
    public function canReadRepositories(): bool
    {
        return $this->hasAny(['repo', 'public_repo', 'read:repo_hook']);
    }

    /**
     * Check if token has write access to repositories
     *
     * @return bool
     */
    public function canWriteRepositories(): bool
    {
        return $this->has('repo');
    }

    /**
     * Check if token has organization read access
     *
     * @return bool
     */
    public function canReadOrganizations(): bool
    {
        return $this->hasAny(['read:org', 'admin:org', 'write:org']);
    }

    /**
     * Get string representation (comma-separated list)
     *
     * @return string
     */
    public function toString(): string
    {
        return implode(', ', $this->scopes);
    }
}
