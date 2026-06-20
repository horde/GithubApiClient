<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use DateTimeImmutable;

/**
 * Data transfer object for updating a Check Run
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
class UpdateCheckRunParams
{
    /**
     * All fields optional. The check-run id rides in the URL, not the body.
     * Empty/null fields drop out of toArray() so a partial update never
     * blanks an existing field by accident; an "all empty" update
     * serializes to [].
     *
     * @param string $name New display name; emitted only when non-empty
     * @param string $headSha New head SHA; emitted only when non-empty
     * @param string $status One of queued, in_progress, completed; emitted only when non-empty
     * @param string $conclusion One of success, failure, neutral, cancelled, skipped, timed_out, action_required; emitted only when non-empty
     * @param DateTimeImmutable|null $startedAt Emitted as ISO 8601 in started_at when set
     * @param DateTimeImmutable|null $completedAt Emitted as ISO 8601 in completed_at when set
     * @param string $detailsUrl Emitted only when non-empty
     * @param string $externalId Emitted only when non-empty
     * @param array<string, mixed>|null $output Passed verbatim when set
     */
    public function __construct(
        public readonly string $name = '',
        public readonly string $headSha = '',
        public readonly string $status = '',
        public readonly string $conclusion = '',
        public readonly ?DateTimeImmutable $startedAt = null,
        public readonly ?DateTimeImmutable $completedAt = null,
        public readonly string $detailsUrl = '',
        public readonly string $externalId = '',
        public readonly ?array $output = null,
    ) {}

    /**
     * Convert to array for API request body. All-empty round-trips to [].
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== '') {
            $data['name'] = $this->name;
        }

        if ($this->headSha !== '') {
            $data['head_sha'] = $this->headSha;
        }

        if ($this->status !== '') {
            $data['status'] = $this->status;
        }

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
