<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * @covers \Horde\GithubApiClient\RateLimit
 */
final class RateLimitTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $rateLimit = new RateLimit(
            limit: 5000,
            remaining: 4999,
            reset: 1609459200,
            used: 1
        );

        $this->assertSame(5000, $rateLimit->limit);
        $this->assertSame(4999, $rateLimit->remaining);
        $this->assertSame(1609459200, $rateLimit->reset);
        $this->assertSame(1, $rateLimit->used);
    }

    public function testFromApiResponseParsesValidResponse(): void
    {
        $response = json_decode('{
            "resources": {
                "core": {
                    "limit": 5000,
                    "remaining": 4999,
                    "reset": 1609459200,
                    "used": 1
                }
            }
        }');

        $rateLimit = RateLimit::fromApiResponse($response);

        $this->assertInstanceOf(RateLimit::class, $rateLimit);
        $this->assertSame(5000, $rateLimit->limit);
        $this->assertSame(4999, $rateLimit->remaining);
        $this->assertSame(1609459200, $rateLimit->reset);
        $this->assertSame(1, $rateLimit->used);
    }

    public function testFromApiResponseThrowsOnInvalidStructure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid rate limit response structure');

        $response = json_decode('{"invalid": "structure"}');
        RateLimit::fromApiResponse($response);
    }

    public function testIsExhaustedReturnsTrueWhenRemainingIsZero(): void
    {
        $rateLimit = new RateLimit(
            limit: 5000,
            remaining: 0,
            reset: 1609459200,
            used: 5000
        );

        $this->assertTrue($rateLimit->isExhausted());
    }

    public function testIsExhaustedReturnsFalseWhenRemainingIsNotZero(): void
    {
        $rateLimit = new RateLimit(
            limit: 5000,
            remaining: 4999,
            reset: 1609459200,
            used: 1
        );

        $this->assertFalse($rateLimit->isExhausted());
    }

    public function testGetUsagePercentageCalculatesCorrectly(): void
    {
        $rateLimit = new RateLimit(
            limit: 5000,
            remaining: 2500,
            reset: 1609459200,
            used: 2500
        );

        $this->assertSame(50.0, $rateLimit->getUsagePercentage());
    }

    public function testGetUsagePercentageReturnsZeroWhenLimitIsZero(): void
    {
        $rateLimit = new RateLimit(
            limit: 0,
            remaining: 0,
            reset: 1609459200,
            used: 0
        );

        $this->assertSame(0.0, $rateLimit->getUsagePercentage());
    }

    public function testGetSecondsUntilResetCalculatesCorrectly(): void
    {
        // Set reset to 1 hour in the future
        $futureReset = time() + 3600;
        $rateLimit = new RateLimit(
            limit: 5000,
            remaining: 4999,
            reset: $futureReset,
            used: 1
        );

        $seconds = $rateLimit->getSecondsUntilReset();
        
        // Allow for some time passage during test execution
        $this->assertGreaterThanOrEqual(3595, $seconds);
        $this->assertLessThanOrEqual(3600, $seconds);
    }

    public function testGetSecondsUntilResetReturnsZeroForPastReset(): void
    {
        // Set reset to 1 hour in the past
        $pastReset = time() - 3600;
        $rateLimit = new RateLimit(
            limit: 5000,
            remaining: 5000,
            reset: $pastReset,
            used: 0
        );

        $this->assertSame(0, $rateLimit->getSecondsUntilReset());
    }

    public function testGetResetDateTimeReturnsCorrectDateTime(): void
    {
        $reset = 1609459200; // 2021-01-01 00:00:00 UTC
        $rateLimit = new RateLimit(
            limit: 5000,
            remaining: 4999,
            reset: $reset,
            used: 1
        );

        $dateTime = $rateLimit->getResetDateTime();

        $this->assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        $this->assertSame('1609459200', $dateTime->format('U'));
    }
}
