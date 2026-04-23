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

use InvalidArgumentException;

class PrivateKey
{
    private function __construct(
        public readonly string $content,
    ) {}

    public static function fromString(string $pemContent): self
    {
        if (trim($pemContent) === '') {
            throw new InvalidArgumentException('Private key content cannot be empty');
        }

        $resource = openssl_pkey_get_private($pemContent);
        if ($resource === false) {
            throw new InvalidArgumentException('Invalid private key format: ' . openssl_error_string());
        }

        return new self($pemContent);
    }

    public static function fromFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Private key file not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("Private key file is not readable: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new InvalidArgumentException("Failed to read private key file: {$filePath}");
        }

        return self::fromString($content);
    }

    /**
     * @return \OpenSSLAsymmetricKey
     */
    public function getResource(): \OpenSSLAsymmetricKey
    {
        $resource = openssl_pkey_get_private($this->content);
        if ($resource === false) {
            throw new InvalidArgumentException('Failed to load private key: ' . openssl_error_string());
        }
        return $resource;
    }
}
