<?php

declare(strict_types=1);

namespace Horde\GithubApiClient\Test\Unit;

use Horde\GithubApiClient\GithubApiClient;
use Horde\GithubApiClient\GithubApiConfig;
use Horde\GithubApiClient\AddLabelsRequestFactory;
use Horde\GithubApiClient\AuthenticatedUserRequestFactory;
use Horde\GithubApiClient\GetAuthenticatedAppRequestFactory;
use Horde\GithubApiClient\ListInstallationsRequestFactory;
use Horde\GithubApiClient\ListRepositoriesInOrganizationRequestFactory;
use Horde\GithubApiClient\GithubOrganizationId;
use Horde\GithubApiClient\GithubRepository;
use Horde\GithubApiClient\RateLimitRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionProperty;

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  GithubApiClient
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
#[CoversClass(GithubApiClient::class)]
#[CoversClass(AuthenticatedUserRequestFactory::class)]
#[CoversClass(RateLimitRequestFactory::class)]
#[CoversClass(AddLabelsRequestFactory::class)]
#[CoversClass(GetAuthenticatedAppRequestFactory::class)]
#[CoversClass(ListInstallationsRequestFactory::class)]
final class PreAuthenticatedClientTest extends TestCase
{
    public function testWithAuthenticatedClientReturnsInstance(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->never())->method('sendRequest');
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->expects($this->never())->method('createRequest');

        $client = GithubApiClient::withAuthenticatedClient($httpClient, $requestFactory);

        self::assertInstanceOf(GithubApiClient::class, $client);
    }

    public function testWithAuthenticatedClientUsesCustomEndpoint(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->never())->method('sendRequest');
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->expects($this->never())->method('createRequest');

        $client = GithubApiClient::withAuthenticatedClient(
            $httpClient,
            $requestFactory,
            endpoint: 'https://github.example.com/api/v3',
        );

        $reflection = new ReflectionProperty(GithubApiClient::class, 'config');
        $config = $reflection->getValue($client);

        self::assertSame('https://github.example.com/api/v3', $config->endpoint);
        self::assertSame('', $config->accessToken);
        self::assertSame('', $config->jwt);
    }

    public function testBearerAccessTokenRequestOmitsAuthWhenEmpty(): void
    {
        $config = new GithubApiConfig();
        $psrRequestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createTrackingRequest();

        $psrRequestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->stringContains('/rate_limit'))
            ->willReturn($request);

        $factory = new RateLimitRequestFactory($psrRequestFactory, $config);
        $result = $factory->create();

        self::assertFalse($result->hasHeader('Authorization'));
    }

    public function testBearerAccessTokenRequestIncludesAuthWhenSet(): void
    {
        $config = new GithubApiConfig(accessToken: 'ghp_test');
        $psrRequestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createTrackingRequest();

        $psrRequestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->stringContains('/rate_limit'))
            ->willReturn($request);

        $factory = new RateLimitRequestFactory($psrRequestFactory, $config);
        $result = $factory->create();

        self::assertTrue($result->hasHeader('Authorization'));
        self::assertSame('Bearer ghp_test', $result->getHeaderLine('Authorization'));
    }

    public function testAuthenticatedUserRequestOmitsAuthWhenEmpty(): void
    {
        $config = new GithubApiConfig();
        $psrRequestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createTrackingRequest();

        $psrRequestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->stringEndsWith('/user'))
            ->willReturn($request);

        $factory = new AuthenticatedUserRequestFactory($psrRequestFactory, $config);
        $result = $factory->create();

        self::assertFalse($result->hasHeader('Authorization'));
    }

    public function testLegacyTokenRequestOmitsAuthWhenEmpty(): void
    {
        $config = new GithubApiConfig();
        $psrRequestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->never())->method($this->anything());
        $request = $this->createTrackingRequest();

        $psrRequestFactory->expects($this->once())
            ->method('createRequest')
            ->with('POST', $this->stringContains('/repos/horde/Core/issues/1/labels'))
            ->willReturn($request);
        $streamFactory->expects($this->once())
            ->method('createStream')
            ->with($this->stringContains('"bug"'))
            ->willReturn($stream);

        $repo = GithubRepository::fromFullName('horde/Core');
        $factory = new AddLabelsRequestFactory(
            $psrRequestFactory,
            $streamFactory,
            $config,
            $repo,
            1,
            ['bug'],
        );
        $result = $factory->create();

        self::assertFalse($result->hasHeader('Authorization'));
    }

    public function testLegacyTokenRequestIncludesAuthWhenSet(): void
    {
        $config = new GithubApiConfig(accessToken: 'ghp_test');
        $psrRequestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->never())->method($this->anything());
        $request = $this->createTrackingRequest();

        $psrRequestFactory->expects($this->once())
            ->method('createRequest')
            ->with('POST', $this->stringContains('/repos/horde/Core/issues/1/labels'))
            ->willReturn($request);
        $streamFactory->expects($this->once())
            ->method('createStream')
            ->with($this->stringContains('"bug"'))
            ->willReturn($stream);

        $repo = GithubRepository::fromFullName('horde/Core');
        $factory = new AddLabelsRequestFactory(
            $psrRequestFactory,
            $streamFactory,
            $config,
            $repo,
            1,
            ['bug'],
        );
        $result = $factory->create();

        self::assertTrue($result->hasHeader('Authorization'));
        self::assertSame('token ghp_test', $result->getHeaderLine('Authorization'));
    }

    public function testJwtRequestOmitsAuthWhenEmpty(): void
    {
        $config = new GithubApiConfig();
        $psrRequestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createTrackingRequest();

        $psrRequestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->stringEndsWith('/app'))
            ->willReturn($request);

        $factory = new GetAuthenticatedAppRequestFactory($psrRequestFactory, $config);
        $result = $factory->create();

        self::assertFalse($result->hasHeader('Authorization'));
    }

    public function testJwtRequestIncludesAuthWhenSet(): void
    {
        $config = new GithubApiConfig(jwt: 'eyJ.test.jwt');
        $psrRequestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createTrackingRequest();

        $psrRequestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->stringEndsWith('/app'))
            ->willReturn($request);

        $factory = new GetAuthenticatedAppRequestFactory($psrRequestFactory, $config);
        $result = $factory->create();

        self::assertTrue($result->hasHeader('Authorization'));
        self::assertSame('Bearer eyJ.test.jwt', $result->getHeaderLine('Authorization'));
    }

    public function testListInstallationsOmitsAuthWhenJwtEmpty(): void
    {
        $config = new GithubApiConfig();
        $psrRequestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createTrackingRequest();

        $psrRequestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->stringContains('/app/installations'))
            ->willReturn($request);

        $factory = new ListInstallationsRequestFactory($psrRequestFactory, $config);
        $result = $factory->create();

        self::assertFalse($result->hasHeader('Authorization'));
    }

    /**
     * Creates a simple request that tracks headers via withHeader()
     * instead of using willReturnSelf() which loses header data.
     */
    private function createTrackingRequest(): RequestInterface
    {
        return new class implements RequestInterface {
            private array $headers = [];
            private string $method = 'GET';
            private \Psr\Http\Message\UriInterface|string $uri = '';

            public function withHeader(string $name, $value): static
            {
                $clone = clone $this;
                $clone->headers[$name] = is_array($value) ? $value : [$value];
                return $clone;
            }

            public function hasHeader(string $name): bool
            {
                return isset($this->headers[$name]);
            }

            public function getHeaderLine(string $name): string
            {
                return isset($this->headers[$name]) ? implode(', ', $this->headers[$name]) : '';
            }

            public function getHeader(string $name): array
            {
                return $this->headers[$name] ?? [];
            }

            public function getHeaders(): array
            {
                return $this->headers;
            }
            public function getProtocolVersion(): string
            {
                return '1.1';
            }
            public function withProtocolVersion(string $version): static
            {
                return $this;
            }
            public function withAddedHeader(string $name, $value): static
            {
                return $this;
            }
            public function withoutHeader(string $name): static
            {
                return $this;
            }
            public function getBody(): StreamInterface
            {
                return new class implements StreamInterface {
                    public function __toString(): string
                    {
                        return '';
                    } public function close(): void {} public function detach()
                    {
                        return null;
                    } public function getSize(): ?int
                    {
                        return 0;
                    } public function tell(): int
                    {
                        return 0;
                    } public function eof(): bool
                    {
                        return true;
                    } public function isSeekable(): bool
                    {
                        return false;
                    } public function seek(int $offset, int $whence = SEEK_SET): void {} public function rewind(): void {} public function isWritable(): bool
                    {
                        return false;
                    } public function write(string $string): int
                    {
                        return 0;
                    } public function isReadable(): bool
                    {
                        return false;
                    } public function read(int $length): string
                    {
                        return '';
                    } public function getContents(): string
                    {
                        return '';
                    } public function getMetadata(?string $key = null)
                    {
                        return null;
                    }
                };
            }
            public function withBody(StreamInterface $body): static
            {
                return $this;
            }
            public function getRequestTarget(): string
            {
                return '/';
            }
            public function withRequestTarget(string $requestTarget): static
            {
                return $this;
            }
            public function getMethod(): string
            {
                return $this->method;
            }
            public function withMethod(string $method): static
            {
                $c = clone $this;
                $c->method = $method;
                return $c;
            }
            public function getUri(): \Psr\Http\Message\UriInterface
            {
                return new class implements \Psr\Http\Message\UriInterface {
                    public function getScheme(): string
                    {
                        return '';
                    } public function getAuthority(): string
                    {
                        return '';
                    } public function getUserInfo(): string
                    {
                        return '';
                    } public function getHost(): string
                    {
                        return '';
                    } public function getPort(): ?int
                    {
                        return null;
                    } public function getPath(): string
                    {
                        return '';
                    } public function getQuery(): string
                    {
                        return '';
                    } public function getFragment(): string
                    {
                        return '';
                    } public function withScheme(string $scheme): static
                    {
                        return $this;
                    } public function withUserInfo(string $user, ?string $password = null): static
                    {
                        return $this;
                    } public function withHost(string $host): static
                    {
                        return $this;
                    } public function withPort(?int $port): static
                    {
                        return $this;
                    } public function withPath(string $path): static
                    {
                        return $this;
                    } public function withQuery(string $query): static
                    {
                        return $this;
                    } public function withFragment(string $fragment): static
                    {
                        return $this;
                    } public function __toString(): string
                    {
                        return '';
                    }
                };
            }
            public function withUri(\Psr\Http\Message\UriInterface $uri, bool $preserveHost = false): static
            {
                return $this;
            }
        };
    }
}
