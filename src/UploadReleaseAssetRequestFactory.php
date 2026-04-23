<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Factory for creating requests to upload a release asset
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
class UploadReleaseAssetRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GithubApiConfig $config,
        private readonly string $uploadUrl,
        private readonly string $filename,
        private readonly StreamInterface $content,
        private readonly string $contentType = 'application/octet-stream',
    ) {}

    /**
     * Create HTTP request to upload a release asset
     *
     * @return RequestInterface
     */
    public function create(): RequestInterface
    {
        // Remove template parameters from upload_url
        // GitHub returns: https://uploads.github.com/repos/:owner/:repo/releases/:id/assets{?name,label}
        $url = preg_replace('/\{[^}]+\}/', '', $this->uploadUrl);

        // Add filename as query parameter
        $url .= '?name=' . rawurlencode($this->filename);

        $request = $this->requestFactory->createRequest('POST', $url);
        if ($this->config->accessToken !== '') {
            $request = $request->withHeader('Authorization', 'token ' . $this->config->accessToken);
        }
        $request = $request->withHeader('Accept', 'application/vnd.github.v3+json');
        $request = $request->withHeader('Content-Type', $this->contentType);
        $request = $request->withBody($this->content);

        return $request;
    }
}
