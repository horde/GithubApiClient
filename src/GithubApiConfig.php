<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Client\ClientInterface;

class GithubApiConfig
{
    public function __construct(
        // Default to public github.com
        public readonly string $endpoint = 'https://api.github.com',
        // Default to no access token
        public readonly string $accessToken = '',
        public readonly string $apiVersion = '2022-11-28'
    ) {
    }
}
