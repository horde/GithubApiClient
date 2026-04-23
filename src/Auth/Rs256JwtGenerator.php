<?php

declare(strict_types=1);

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @author   Ralf Lang <ralf.lang@ralf-lang.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\GithubApiClient\Auth;

use RuntimeException;

class Rs256JwtGenerator implements JwtGeneratorInterface
{
    public function generate(
        int $appId,
        PrivateKey $privateKey,
        int $expirySeconds = 600,
    ): GeneratedJwt {
        if ($expirySeconds <= 0) {
            throw new RuntimeException('Expiry seconds must be positive');
        }

        if ($expirySeconds > 600) {
            throw new RuntimeException('GitHub App JWTs cannot have expiry greater than 600 seconds (10 minutes)');
        }

        $now = time();
        $expiresAt = $now + $expirySeconds;

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $appId,
            'iat' => $now,
            'exp' => $expiresAt,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        $signatureBase = "{$headerEncoded}.{$payloadEncoded}";
        $signature = $this->sign($signatureBase, $privateKey);

        return new GeneratedJwt("{$signatureBase}.{$signature}", $expiresAt);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function sign(string $data, PrivateKey $privateKey): string
    {
        $keyResource = $privateKey->getResource();

        $signature = '';
        $success = openssl_sign($data, $signature, $keyResource, OPENSSL_ALGO_SHA256);

        if (!$success) {
            throw new RuntimeException('Failed to sign JWT: ' . openssl_error_string());
        }

        return $this->base64UrlEncode($signature);
    }
}
