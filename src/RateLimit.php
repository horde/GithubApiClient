<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

/**
 * Represents GitHub API rate limit information
 */
class RateLimit
{
    public function __construct(
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $reset,
        public readonly int $used
    ) {}

    /**
     * Create from API response
     *
     * @param object $response Decoded JSON response from /rate_limit endpoint
     * @return self
     */
    public static function fromApiResponse(object $response): self
    {
        $core = $response->resources->core ?? throw new \InvalidArgumentException('Invalid rate limit response structure');

        return new self(
            limit: $core->limit ?? 0,
            remaining: $core->remaining ?? 0,
            reset: $core->reset ?? 0,
            used: $core->used ?? 0
        );
    }

    /**
     * Get time until rate limit resets
     *
     * @return int Seconds until reset
     */
    public function getSecondsUntilReset(): int
    {
        return max(0, $this->reset - time());
    }

    /**
     * Check if rate limit is exhausted
     *
     * @return bool
     */
    public function isExhausted(): bool
    {
        return $this->remaining === 0;
    }

    /**
     * Get percentage of quota used
     *
     * @return float Percentage (0-100)
     */
    public function getUsagePercentage(): float
    {
        if ($this->limit === 0) {
            return 0.0;
        }
        return ($this->used / $this->limit) * 100;
    }

    /**
     * Get reset time as DateTime
     *
     * @return \DateTimeImmutable
     */
    public function getResetDateTime(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('U', (string) $this->reset)
            ?: throw new \RuntimeException('Failed to create DateTime from reset timestamp');
    }
}
