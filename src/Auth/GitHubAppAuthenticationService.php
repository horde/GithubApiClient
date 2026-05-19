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

use Horde\GithubApiClient\CreateInstallationAccessTokenParams;
use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\InstallationAccessToken;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Exception;

class GitHubAppAuthenticationService
{
    private ?InstallationAccessToken $cachedToken = null;
    private ?int $tokenExpiresAt = null;

    public function __construct(
        private readonly GitHubAppConfig $config,
        private readonly JwtGeneratorInterface $jwtGenerator,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    public function getAuthenticatedClient(): GithubApiClient
    {
        $token = $this->getInstallationAccessToken();

        $config = new GithubApiConfig(
            accessToken: $token->token,
        );

        return new GithubApiClient(
            $this->httpClient,
            $this->requestFactory,
            $config,
            $this->streamFactory,
        );
    }

    public function getJwtAuthenticatedClient(): GithubApiClient
    {
        $privateKey = PrivateKey::fromFile($this->config->privateKeyPath);

        $jwt = $this->jwtGenerator->generate(
            $this->config->appId,
            $privateKey,
            540,
        );

        $jwtConfig = new GithubApiConfig(jwt: $jwt->token);

        return new GithubApiClient(
            $this->httpClient,
            $this->requestFactory,
            $jwtConfig,
            $this->streamFactory,
        );
    }

    public function clearCache(): void
    {
        $this->cachedToken = null;
        $this->tokenExpiresAt = null;
    }

    /**
     * Return a short-lived installation access token string.
     *
     * Useful when callers need raw HTTP access to the GitHub API
     * without going through GithubApiClient (e.g. fetching file
     * contents via the Contents API).
     */
    public function getInstallationToken(): string
    {
        return $this->getInstallationAccessToken()->token;
    }

    public function hasValidCachedToken(): bool
    {
        if ($this->cachedToken === null || $this->tokenExpiresAt === null) {
            return false;
        }

        return time() < ($this->tokenExpiresAt - 60);
    }

    private function getInstallationAccessToken(): InstallationAccessToken
    {
        if ($this->cachedToken !== null && $this->tokenExpiresAt !== null) {
            if (time() < ($this->tokenExpiresAt - 60)) {
                return $this->cachedToken;
            }
        }

        $this->cachedToken = $this->generateInstallationAccessToken();
        $this->tokenExpiresAt = $this->parseExpiryFromToken($this->cachedToken);

        return $this->cachedToken;
    }

    private function generateInstallationAccessToken(): InstallationAccessToken
    {
        $privateKey = PrivateKey::fromFile($this->config->privateKeyPath);

        $jwt = $this->jwtGenerator->generate(
            $this->config->appId,
            $privateKey,
            540,
        );

        $jwtConfig = new GithubApiConfig(jwt: $jwt->token);
        $jwtClient = new GithubApiClient(
            $this->httpClient,
            $this->requestFactory,
            $jwtConfig,
            $this->streamFactory,
        );

        try {
            return $jwtClient->createInstallationAccessToken(
                $this->config->installationId,
                new CreateInstallationAccessTokenParams(),
            );
        } catch (Exception $e) {
            throw new RuntimeException(
                "Failed to create installation access token: {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    private function parseExpiryFromToken(InstallationAccessToken $token): int
    {
        $timestamp = strtotime($token->expiresAt);
        if ($timestamp === false) {
            return time() + 3600;
        }
        return $timestamp;
    }
}
