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

interface JwtGeneratorInterface
{
    /**
     * @throws RuntimeException If JWT generation fails
     */
    public function generate(
        int $appId,
        PrivateKey $privateKey,
        int $expirySeconds = 600,
    ): GeneratedJwt;
}
