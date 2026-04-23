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

class GeneratedJwt
{
    public function __construct(
        public readonly string $token,
        public readonly int $expiresAt,
    ) {}

    public function isExpired(): bool
    {
        return time() >= $this->expiresAt;
    }

    public function getSecondsUntilExpiration(): int
    {
        return $this->expiresAt - time();
    }
}
