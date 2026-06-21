<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Data transfer object for updating an organization-level issue type.
 *
 * All fields optional. All-null toArray() returns [] so no-op updates are
 * harmless.
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
class UpdateIssueTypeParams
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?string $color = null,
        public readonly ?bool $isEnabled = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->color !== null) {
            $data['color'] = $this->color;
        }
        if ($this->isEnabled !== null) {
            $data['is_enabled'] = $this->isEnabled;
        }

        return $data;
    }
}
