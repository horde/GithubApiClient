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

class GitHubAppConfig
{
    public function __construct(
        public readonly int $appId,
        public readonly int $installationId,
        public readonly string $privateKeyPath,
    ) {
        if ($this->appId <= 0) {
            throw new InvalidArgumentException('GitHub App ID must be positive');
        }

        if ($this->installationId <= 0) {
            throw new InvalidArgumentException('GitHub App Installation ID must be positive');
        }

        if (trim($this->privateKeyPath) === '') {
            throw new InvalidArgumentException('Private key path cannot be empty');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        if (!isset($config['app_id'])) {
            throw new InvalidArgumentException('Missing required config key: app_id');
        }

        if (!isset($config['installation_id'])) {
            throw new InvalidArgumentException('Missing required config key: installation_id');
        }

        if (!isset($config['private_key_path'])) {
            throw new InvalidArgumentException('Missing required config key: private_key_path');
        }

        return new self(
            appId: (int) $config['app_id'],
            installationId: (int) $config['installation_id'],
            privateKeyPath: (string) $config['private_key_path'],
        );
    }

    public static function fromEnvironment(): self
    {
        $appId = getenv('GITHUB_APP_ID');
        $installationId = getenv('GITHUB_APP_INSTALLATION_ID');
        $privateKeyPath = getenv('GITHUB_APP_PRIVATE_KEY_PATH');

        if ($appId === false || $appId === '') {
            throw new InvalidArgumentException('Missing environment variable: GITHUB_APP_ID');
        }

        if ($installationId === false || $installationId === '') {
            throw new InvalidArgumentException('Missing environment variable: GITHUB_APP_INSTALLATION_ID');
        }

        if ($privateKeyPath === false || $privateKeyPath === '') {
            throw new InvalidArgumentException('Missing environment variable: GITHUB_APP_PRIVATE_KEY_PATH');
        }

        return new self(
            appId: (int) $appId,
            installationId: (int) $installationId,
            privateKeyPath: (string) $privateKeyPath,
        );
    }
}
