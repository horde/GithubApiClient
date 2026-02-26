#!/usr/bin/env php
<?php

/**
 * Demo script showing rate limit and token scope checking
 */
declare(strict_types=1);

namespace Horde\GithubApiClient;

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Horde\Injector\Injector;
use Horde\Injector\TopLevel;
use Horde\Http\Client\Options;
use Horde\Http\Client\Curl as CurlClient;
use Horde\Http\StreamFactory;
use Horde\Http\RequestFactory;
use Horde\Http\ResponseFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

// Check for GitHub token
$strGithubApiToken = (string) getenv('GITHUB_TOKEN');
if (empty($strGithubApiToken)) {
    echo "\nERROR: No GITHUB_TOKEN environment variable set.\n";
    echo "Export your GitHub personal access token:\n";
    echo "  export GITHUB_TOKEN=ghp_your_token_here\n\n";
    exit(1);
}

// Bootstrap the injector
$injector = new Injector(new TopLevel());
$injector->setInstance(ClientInterface::class, new CurlClient(new ResponseFactory(), new StreamFactory(), new Options()));
$injector->setInstance(RequestFactoryInterface::class, new RequestFactory());
$injector->setInstance(GithubApiConfig::class, new GithubApiConfig(accessToken: $strGithubApiToken));

$client = $injector->get(GithubApiClient::class);

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║          GitHub API Token Information                        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Get Rate Limit
try {
    echo "📊 Checking Rate Limit...\n";
    $rateLimit = $client->getRateLimit();
    
    echo "  ├─ Limit:     " . number_format($rateLimit->limit) . " requests/hour\n";
    echo "  ├─ Used:      " . number_format($rateLimit->used) . " requests\n";
    echo "  ├─ Remaining: " . number_format($rateLimit->remaining) . " requests\n";
    echo "  ├─ Usage:     " . number_format($rateLimit->getUsagePercentage(), 1) . "%\n";
    
    if ($rateLimit->isExhausted()) {
        echo "  └─ ⚠️  EXHAUSTED - Resets at " . $rateLimit->getResetDateTime()->format('Y-m-d H:i:s T') . "\n";
    } else {
        $seconds = $rateLimit->getSecondsUntilReset();
        $minutes = floor($seconds / 60);
        echo "  └─ ✓ Resets in " . $minutes . " minutes (" . $rateLimit->getResetDateTime()->format('Y-m-d H:i:s T') . ")\n";
    }
    
    echo "\n";
} catch (\Exception $e) {
    echo "  └─ ✗ Error: " . $e->getMessage() . "\n\n";
}

// Get Token Scopes
try {
    echo "🔐 Checking Token Scopes/Permissions...\n";
    $scopes = $client->getTokenScopes();
    
    if ($scopes->isEmpty()) {
        echo "  └─ ⚠️  No scopes granted (token may be invalid)\n\n";
    } else {
        echo "  ├─ Total Scopes: " . $scopes->count() . "\n";
        echo "  ├─ Granted: " . $scopes->toString() . "\n";
        echo "  │\n";
        echo "  ├─ Capabilities:\n";
        echo "  │   ├─ Read Repositories:  " . ($scopes->canReadRepositories() ? "✓ YES" : "✗ NO") . "\n";
        echo "  │   ├─ Write Repositories: " . ($scopes->canWriteRepositories() ? "✓ YES" : "✗ NO") . "\n";
        echo "  │   └─ Read Organizations: " . ($scopes->canReadOrganizations() ? "✓ YES" : "✗ NO") . "\n";
        echo "  │\n";
        
        // List all individual scopes
        echo "  └─ Individual Scopes:\n";
        foreach ($scopes->toArray() as $scope) {
            echo "      • " . $scope . "\n";
        }
    }
    
    echo "\n";
} catch (\Exception $e) {
    echo "  └─ ✗ Error: " . $e->getMessage() . "\n\n";
}

echo "════════════════════════════════════════════════════════════════\n\n";
