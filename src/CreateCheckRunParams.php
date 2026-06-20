<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use DateTimeImmutable;

/**
 * Data transfer object for creating a Check Run
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
class CreateCheckRunParams
{
    /**
     * @param string $name Display name of the check (required)
     * @param string $headSha SHA of the commit being checked (required)
     * @param string $status One of queued, in_progress, completed (default: completed)
     * @param string $conclusion One of success, failure, neutral, cancelled, skipped, timed_out, action_required. Required by GitHub when status === 'completed'; only emitted when non-empty
     * @param DateTimeImmutable|null $startedAt When the check started; emitted as ISO 8601 in started_at
     * @param DateTimeImmutable|null $completedAt When the check completed; emitted as ISO 8601 in completed_at
     * @param string $detailsUrl URL the user lands on when clicking the check (default: empty, omitted)
     * @param string $externalId External reference id (default: empty, omitted)
     * @param array<string, mixed>|null $output Output payload: {title, summary, text?, annotations?, images?}; passed verbatim when set
     */
    public function __construct(
        public readonly string $name,
        public readonly string $headSha,
        public readonly string $status = 'completed',
        public readonly string $conclusion = '',
        public readonly ?DateTimeImmutable $startedAt = null,
        public readonly ?DateTimeImmutable $completedAt = null,
        public readonly string $detailsUrl = '',
        public readonly string $externalId = '',
        public readonly ?array $output = null,
    ) {}

    /**
     * Convert to array for API request body.
     *
     * Required fields always emit. Optional fields drop out when at their
     * default (empty string / null).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'head_sha' => $this->headSha,
            'status' => $this->status,
        ];

        if ($this->conclusion !== '') {
            $data['conclusion'] = $this->conclusion;
        }

        if ($this->startedAt !== null) {
            $data['started_at'] = $this->startedAt->format(DATE_ATOM);
        }

        if ($this->completedAt !== null) {
            $data['completed_at'] = $this->completedAt->format(DATE_ATOM);
        }

        if ($this->detailsUrl !== '') {
            $data['details_url'] = $this->detailsUrl;
        }

        if ($this->externalId !== '') {
            $data['external_id'] = $this->externalId;
        }

        if ($this->output !== null) {
            $data['output'] = $this->output;
        }

        return $data;
    }
}
