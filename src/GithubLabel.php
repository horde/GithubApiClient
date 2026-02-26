<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents a GitHub label
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class GithubLabel implements Stringable
{
    public function __construct(
        public readonly string $name,
        public readonly string $color,
        public readonly ?string $description = null
    ) {}

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Create GithubLabel from GitHub API response
     *
     * @param object $data Decoded JSON from API
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            name: $data->name ?? '',
            color: $data->color ?? '000000',
            description: $data->description ?? null
        );
    }
}
