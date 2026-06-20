<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Exception;

/**
 * Thrown when GitHub returns 403 with body "Resource not accessible by integration".
 *
 * This is the standard symptom of a missing scope on the access token — for example
 * an Actions workflow that does not declare `permissions: pull-requests: write` (or
 * `checks: write`) when calling endpoints that require it. The library surfaces a
 * typed exception so consumers can print a maintainer-actionable hint instead of a
 * generic stack trace; non-403 errors and 403 errors with other body shapes still
 * route through the plain Exception path.
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class GithubApiAccessDeniedException extends Exception
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $responseBody,
        public readonly string $hint,
        string $message = ''
    ) {
        parent::__construct(
            $message !== '' ? $message : sprintf('%d Forbidden: %s', $statusCode, $hint)
        );
    }
}
