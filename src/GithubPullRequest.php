<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use stdClass;
use Stringable;
use InvalidArgumentException;

class GithubPullRequest implements Stringable
{
    public function __construct(
        public readonly stdClass $originalApiArray = new stdClass(),
    ) {}

    public function getNumber(): int
    {
        return $this->originalApiArray->number ?? 0;
    }

    public function getTitle(): string
    {
        return $this->originalApiArray->title ?? '';
    }

    public function getHtmlUrl(): string
    {
        return $this->originalApiArray->html_url ?? '';
    }
    public function getApiUrl(): string
    {
        return $this->originalApiArray->url ?? '';
    }

    public function getState(): string
    {
        return $this->originalApiArray->state ?? '';
    }

    public function getBaseRepo(): GithubRepository
    {
        return $this->baseRepo;
    }

    public function getHeadRepo(): GithubRepository
    {
        return $this->headRepo;
    }

    public function __toString(): string
    {
        return $this->getHtmlUrl();
    }
}
