<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Stringable;

/**
 * Represents the result of a pull request merge
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class MergeResult implements Stringable
{
    public function __construct(
        public readonly string $sha,
        public readonly bool $merged,
        public readonly string $message
    ) {}

    public function __toString(): string
    {
        return $this->merged ? sprintf('Merged: %s (%s)', $this->message, $this->sha) : sprintf('Not merged: %s', $this->message);
    }

    /**
     * Create from GitHub API response
     *
     * @param object $data The API response data
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            sha: $data->sha ?? '',
            merged: $data->merged ?? false,
            message: $data->message ?? ''
        );
    }
}
