<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit\Webhook;

use Horde\GithubApiClient\Webhook\WebhookSignatureVerifier;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(WebhookSignatureVerifier::class)]
class WebhookSignatureVerifierTest extends TestCase
{
    private const SECRET = 'test-webhook-secret';

    private function sign(string $payload, string $secret = self::SECRET): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }

    public function testValidSignaturePasses(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET);
        $payload = '{"action":"opened"}';

        $this->assertTrue($verifier->verify($payload, $this->sign($payload)));
    }

    public function testWrongSecretFails(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET);
        $payload = '{"action":"opened"}';

        $this->assertFalse($verifier->verify($payload, $this->sign($payload, 'wrong-secret')));
    }

    public function testTamperedPayloadFails(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET);
        $original = '{"action":"opened"}';
        $tampered = '{"action":"closed"}';

        $this->assertFalse($verifier->verify($tampered, $this->sign($original)));
    }

    public function testEmptySignatureFails(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET);

        $this->assertFalse($verifier->verify('{}', ''));
    }

    public function testMissingPrefixFails(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET);
        $payload = '{}';
        $bareHash = hash_hmac('sha256', $payload, self::SECRET);

        $this->assertFalse($verifier->verify($payload, $bareHash));
    }

    public function testSha1PrefixFails(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET);
        $payload = '{}';
        $sha1Sig = 'sha1=' . hash_hmac('sha1', $payload, self::SECRET);

        $this->assertFalse($verifier->verify($payload, $sha1Sig));
    }
}
