<?php

declare(strict_types=1);

namespace F4\Core;

use ErrorException;

use Composer\Pcre\Preg;

use F4\Config;
use F4\Core\RequestInterface;
use F4\Core\StateAwareTrait;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Psr\Http\Message\UriInterface as PsrUriInterface;

/**
 * 
 * Directly extending Nyholm\Psr7\ServerRequest is heavily discouraged by the developers of Nyholm\* package
 * 
 */
class Request implements RequestInterface 
{
    protected PsrServerRequestInterface $psrRequest;
    protected string $path;
    protected ?string $extension = null;
    protected ?string $debugExtension = null;
    protected ?string $language = null;

    public function __construct(?PsrServerRequestInterface $psrRequest = null)
    {
        $psr17Factory = new Psr17Factory();
        $this->setPsrRequest(psrRequest: match($psrRequest) {
            null => (new ServerRequestCreator(
                $psr17Factory, // ServerRequestFactory
                $psr17Factory, // UriFactory
                $psr17Factory, // UploadedFileFactory
                $psr17Factory  // StreamFactory
            ))->fromGlobals(),
            default => $psrRequest
        });
        $this->initialize();
    }

    public function initialize() {
        $languages = [Config::DEFAULT_LANGUAGE, ...array_keys(Config::DICTIONARIES)];
        $languagesPattern =
            \implode(separator: '|', array: \array_map(callback: function ($language): string {
                return \preg_quote(str: $language, delimiter: '/');
            }, array: $languages));
        $extensions = $this->getAvailableExtensions();
        $extensionsPattern =
            \implode(separator: '|', array: \array_map(callback: function ($extension): string {
                return \preg_quote(str: $extension, delimiter: '/');
            }, array: $extensions));
        $debugExtensions = $this->getAvailableDebugExtensions();
        $debugExtensionsPattern =
            \implode(separator: '|', array: \array_map(callback: function ($debugExtension): string {
                return \preg_quote(str: $debugExtension, delimiter: '/');
            }, array: $debugExtensions));
        if (!Preg::isMatch(pattern: "/(?<path>\/.*?)(?<extension>{$extensionsPattern})?(\((?<language>{$languagesPattern})\))?(?<debugExtension>{$debugExtensionsPattern})?$/Anu", subject: $this->getUri()->getPath(), matches: $matches)) {
            throw new ErrorException(message: 'request-uri-cannot-be-parsed');
        }
        $this->path = $matches['path'];
        $this->extension = $matches['extension'];
        $this->language = $matches['language'];
        $this->debugExtension = match(Config::DEBUG_MODE) {
            true => $matches['debugExtension'],
            default => null
        };
    }
    static public function fromPsr(psrServerRequestInterface $psrRequest): static {
        return new self(psrRequest: $psrRequest);
    }
    protected function getAvailableExtensions(): array
    {
        return \array_reduce(
            array: Config::RESPONSE_RENDERERS,
            callback: function ($extensions, $rendererConfiguration): array {
                return [...$extensions, ...$rendererConfiguration['extensions'] ?? []];
            },
            initial: []
        );
    }
    protected function getAvailableDebugExtensions(): array
    {
        return \array_reduce(
            array: 
                \array_filter(
                    array: Config::RESPONSE_RENDERERS, 
                    callback: function($rendererConfiguration): bool {
                        return isset($rendererConfiguration['debug-extension']);
                    }
                ),
            callback: 
                function ($debugExtensions, $rendererConfiguration): array {
                    return [...$debugExtensions, $rendererConfiguration['debug-extension'] ?? []];
                },
            initial: []
        );
    }
    public function getDebugExtension(): ?string
    {
        return $this->debugExtension;
    }
    public function getExtension(): ?string
    {
        return $this->extension;
    }
    public function getLanguage(): ?string
    {
        return $this->language;
    }
    public function getPath(): string
    {
        return $this->path;
    }
    public function checkIfPathMatches(string $regexp): bool
    {
        return Preg::isMatch($regexp, $this->path);
    }
    public function setPsrRequest(PsrServerRequestInterface $psrRequest): static
    {
        $this->psrRequest = $psrRequest;
        $this->initialize();
        return $this;
    }
    public function getPsrRequest(): PsrServerRequestInterface
    {
        return $this->psrRequest;
    }

    // Wrappers around PSR

    // MessageInterface
    public function getProtocolVersion(): string
    {
        return $this->psrRequest->getProtocolVersion();
    }
    public function withProtocolVersion(string $version): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withProtocolVersion($version));
        return $new;
    }
    public function getHeaders(): array
    {
        return $this->psrRequest->getHeaders();
    }
    public function hasHeader(string $name): bool
    {
        return $this->psrRequest->hasHeader($name);
    }
    public function getHeader(string $name): array
    {
        return $this->psrRequest->getHeader($name);
    }
    public function getHeaderLine(string $name): string
    {
        return $this->psrRequest->getHeaderLine($name);
    }
    public function withHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withHeader($name, $value));
        return $new;
    }
    public function withAddedHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withAddedHeader($name, $value));
        return $new;
    }
    public function withoutHeader(string $name): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withoutHeader($name));
        return $new;
    }
    public function getBody(): PsrStreamInterface
    {
        return $this->psrRequest->getBody();
    }
    public function withBody(PsrStreamInterface $body): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withBody($body));
        return $new;
    }

    // RequestInterface
    public function getRequestTarget(): string
    {
        return $this->psrRequest->getRequestTarget();
    }
    public function withRequestTarget(string $requestTarget): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withRequestTarget($requestTarget));
        return $new;
    }
    public function getMethod(): string
    {
        return $this->psrRequest->getMethod();
    }
    public function withMethod(string $method): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withMethod($method));
        return $new;
    }
    public function getUri(): PsrUriInterface
    {
        return $this->psrRequest->getUri();
    }
    public function withUri(PsrUriInterface $uri, bool $preserveHost = false): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withUri($uri, $preserveHost));
        return $new;
    }

    // ServerRequestInterface
    public function getServerParams(): array
    {
        return $this->psrRequest->getServerParams();
    }
    public function getCookieParams(): array
    {
        return $this->psrRequest->getCookieParams();
    }
    public function withCookieParams(array $cookies): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withCookieParams($cookies));
        return $new;
    }
    public function getQueryParams(): array
    {
        return $this->psrRequest->getQueryParams();
    }
    public function withQueryParams(array $query): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withQueryParams($query));
        return $new;
    }
    public function getUploadedFiles(): array
    {
        return $this->psrRequest->getUploadedFiles();
    }
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withUploadedFiles($uploadedFiles));
        return $new;
    }
    public function getParsedBody()
    {
        return $this->psrRequest->getParsedBody();
    }
    public function withParsedBody($data): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withParsedBody($data));
        return $new;
    }
    public function getAttributes(): array
    {
        return $this->psrRequest->getAttributes();
    }
    public function getAttribute(string $name, $default = null): mixed
    {
        return $this->psrRequest->getAttribute($name, $default);
    }
    public function withAttribute(string $name, $value): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withAttribute($name, $value));
        return $new;
    }
    public function withoutAttribute(string $name): static
    {
        $new = clone $this;
        $new->setPsrRequest(psrRequest: $this->psrRequest->withoutAttribute($name));
        return $new;
    }

}