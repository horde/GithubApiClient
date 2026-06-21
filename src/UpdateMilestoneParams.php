<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use DateTimeImmutable;

/**
 * Data transfer object for updating a milestone.
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
class UpdateMilestoneParams
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $state = null,
        public readonly ?string $description = null,
        public readonly ?DateTimeImmutable $dueOn = null
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }
        if ($this->state !== null) {
            $data['state'] = $this->state;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->dueOn !== null) {
            $data['due_on'] = $this->dueOn->format(DATE_ATOM);
        }

        return $data;
    }
}
