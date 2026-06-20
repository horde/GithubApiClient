<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for creating a repository-level label definition
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
class CreateLabelParams
{
    /**
     * @param string $name Label name (required)
     * @param string $color 6-hex color without leading '#' (required). Not validated here; GitHub rejects malformed values
     * @param string $description Optional description; emitted only when non-empty
     */
    public function __construct(
        public readonly string $name,
        public readonly string $color,
        public readonly string $description = ''
    ) {}

    /**
     * Convert to array for API request body.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'color' => $this->color,
        ];

        if ($this->description !== '') {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
