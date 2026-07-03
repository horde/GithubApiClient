<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Partial-update DTO for an issue.
 *
 * Uses a with*() builder + per-field "was-touched" flag so we can distinguish
 * three states per field: untouched (omit from request body), set to a value
 * (emit value), set to null (emit literal null). The last distinction matters
 * because GitHub's `milestone` and `type` fields use null as the "clear current
 * assignment" signal; emitting the key with null is meaningfully different
 * from omitting it.
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
class IssueUpdate
{
    private ?string $title = null;
    private bool $titleSet = false;

    private ?string $body = null;
    private bool $bodySet = false;

    private ?string $state = null;
    private bool $stateSet = false;

    private ?string $stateReason = null;
    private bool $stateReasonSet = false;

    /** @var string[] */
    private array $labels = [];
    private bool $labelsSet = false;

    /** @var string[] */
    private array $assignees = [];
    private bool $assigneesSet = false;

    private ?int $milestone = null;
    private bool $milestoneSet = false;

    private ?string $type = null;
    private bool $typeSet = false;

    public function withTitle(?string $title): self
    {
        $clone = clone $this;
        $clone->title = $title;
        $clone->titleSet = true;
        return $clone;
    }

    public function withBody(?string $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        $clone->bodySet = true;
        return $clone;
    }

    /**
     * @param string $state 'open' or 'closed'
     */
    public function withState(string $state): self
    {
        $clone = clone $this;
        $clone->state = $state;
        $clone->stateSet = true;
        return $clone;
    }

    /**
     * @param string|null $reason 'completed', 'not_planned', 'reopened', or null to clear
     */
    public function withStateReason(?string $reason): self
    {
        $clone = clone $this;
        $clone->stateReason = $reason;
        $clone->stateReasonSet = true;
        return $clone;
    }

    /**
     * Replace the full set of labels. Pass [] to clear all labels.
     *
     * @param string[] $labels
     */
    public function withLabels(array $labels): self
    {
        $clone = clone $this;
        $clone->labels = $labels;
        $clone->labelsSet = true;
        return $clone;
    }

    /**
     * Replace the full set of assignees. Pass [] to clear all assignees.
     *
     * @param string[] $assignees Usernames
     */
    public function withAssignees(array $assignees): self
    {
        $clone = clone $this;
        $clone->assignees = $assignees;
        $clone->assigneesSet = true;
        return $clone;
    }

    /**
     * Assign or clear a milestone.
     *
     * @param int|null $milestoneNumber Milestone number to assign, or null to clear.
     */
    public function withMilestone(?int $milestoneNumber): self
    {
        $clone = clone $this;
        $clone->milestone = $milestoneNumber;
        $clone->milestoneSet = true;
        return $clone;
    }

    /**
     * Assign or clear an issue type.
     *
     * @param string|null $typeName Issue type name to assign, or null to clear.
     */
    public function withType(?string $typeName): self
    {
        $clone = clone $this;
        $clone->type = $typeName;
        $clone->typeSet = true;
        return $clone;
    }

    /**
     * Convert to array for API request body. Only emits keys that were
     * explicitly touched via a with*() call; emits null for fields
     * explicitly set to null (the GitHub "clear" signal).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->titleSet) {
            $data['title'] = $this->title;
        }
        if ($this->bodySet) {
            $data['body'] = $this->body;
        }
        if ($this->stateSet) {
            $data['state'] = $this->state;
        }
        if ($this->stateReasonSet) {
            $data['state_reason'] = $this->stateReason;
        }
        if ($this->labelsSet) {
            $data['labels'] = $this->labels;
        }
        if ($this->assigneesSet) {
            $data['assignees'] = $this->assignees;
        }
        if ($this->milestoneSet) {
            $data['milestone'] = $this->milestone;
        }
        if ($this->typeSet) {
            $data['type'] = $this->type;
        }

        return $data;
    }

    /**
     * True when no with*() builder has been called yet.
     */
    public function isEmpty(): bool
    {
        return !$this->titleSet
            && !$this->bodySet
            && !$this->stateSet
            && !$this->stateReasonSet
            && !$this->labelsSet
            && !$this->assigneesSet
            && !$this->milestoneSet
            && !$this->typeSet;
    }
}
