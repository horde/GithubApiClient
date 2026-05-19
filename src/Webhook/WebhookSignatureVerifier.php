<?php

declare(strict_types=1);

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @license http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\GithubApiClient\Webhook;

class WebhookSignatureVerifier
{
    public function __construct(
        private readonly string $secret,
    ) {}

    public function verify(string $payload, string $signatureHeader): bool
    {
        if ($signatureHeader === '' || !str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $this->secret);

        return hash_equals($expected, $signatureHeader);
    }
}
