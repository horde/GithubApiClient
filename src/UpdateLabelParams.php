<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for updating a repository-level label definition.
 *
 * All fields are optional. The URL path segment {currentName} is the key;
 * a non-null `name` in the body renames the label. Empty/null fields drop
 * out of toArray() so a no-op update serializes to [].
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
class UpdateLabelParams
{
    /**
     * @param string|null $name New name; non-null triggers a rename
     * @param string|null $color New 6-hex color without leading '#'
     * @param string|null $description New description; pass empty string to set a literal empty value
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $color = null,
        public readonly ?string $description = null
    ) {}

    /**
     * Convert to array for API request body. All-null returns [].
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->color !== null) {
            $data['color'] = $this->color;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
