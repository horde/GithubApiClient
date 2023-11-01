<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Client\ClientInterface;
use Stringable;

class GithubOrganizationId implements Stringable
{
    public function __construct(
        // Default to public github.com
        public readonly Stringable|string $org,
    ) {
    }

    public function __toString(): string
    {
        return (string) $this->org;
    }
}
