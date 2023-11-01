<?php

declare(strict_types=1);

namespace Horde\GithubApiClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Horde\Http\Uri;

class GithubApiPagination
{
    /**
     * @var string[]
     */
    private array $relations = [];

    public function __construct(private readonly RequestInterface $request, private readonly ResponseInterface $response)
    {
        // Github separates links by comma-space rather than having a comma-separated list of link header values
        $headerLine = $this->response->getHeaderLine('Link');
        $headers = explode(', ', $headerLine);
        foreach ($headers as $header) {
            [$origLink, $origRel] = explode('; ', $header);
            $link = trim($origLink, "<>");
            preg_match_all('|rel="(.*)"|', $origRel, $matches);
            // TODO: Exception if matches is null or count($matches) != 2 or offset 1/0 does not exist
            $rel = $matches[1][0];
            $this->relations[$rel] = $link;
        }
    }

    public function hasNextLink(): bool
    {
        return array_key_exists('next', $this->relations);
    }

    public function hasLastLink(): bool
    {
        return array_key_exists('last', $this->relations);
    }

    public function hasFirstLink(): bool
    {
        return array_key_exists('first', $this->relations);
    }

    public function hasPrevLink(): bool
    {
        return array_key_exists('prev', $this->relations);
    }

    public function firstRequest(): RequestInterface
    {
        // If it doesn't have the first link, it must be the first page already
        $uri = $this->hasFirstLink() ? $this->relations['first'] : $this->request->getUri();
        return $this->request->withUri(new Uri((string) $uri));
    }

    public function nextRequest(): RequestInterface
    {
        return $this->request->withUri(new Uri($this->relations['next']));
    }
}
