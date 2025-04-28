<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use stdClass;
use Stringable;
use InvalidArgumentException;

class GithubPullRequest implements Stringable
{
    public function __construct(
        public readonly int $number,
        public readonly string $title,
        public readonly string $htmlUrl,
        public readonly string $apiUrl,
        public readonly string $state,
        public readonly GithubRepository $baseRepo,
        public readonly GithubRepository $headRepo,
    ) {}

    public function __toString(): string
    {
        return $this->htmlUrl;
    }
}
