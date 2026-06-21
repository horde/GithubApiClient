<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for creating an organization-level issue type
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
class CreateIssueTypeParams
{
    /**
     * @param string $name Type name (required), e.g. "Bug"
     * @param string $description Optional description; emitted only when non-empty
     * @param string $color One of GitHub's enum colors (gray|blue|green|yellow|orange|red|pink|purple); emitted only when non-empty
     * @param bool $isEnabled Whether the type is active in the org's UI (default: true)
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description = '',
        public readonly string $color = '',
        public readonly bool $isEnabled = true
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'is_enabled' => $this->isEnabled,
        ];

        if ($this->description !== '') {
            $data['description'] = $this->description;
        }
        if ($this->color !== '') {
            $data['color'] = $this->color;
        }

        return $data;
    }
}
