<?php

declare(strict_types=1);

namespace F4\Core;

use ErrorException;

use F4\Config;
use F4\Core\ResponseInterface;
use F4\Core\StateAwareTrait;

use Nyholm\Psr7\Factory\Psr17Factory;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * 
 * Directly extending Nyholm\Psr7\Response is heavily discouraged by the developers of Nyholm\* package
 * 
 */
class Response implements ResponseInterface
{
    protected PsrResponseInterface $psrResponse;
    protected array $bodyFragments = [];
    protected string $responseFormat = Config::DEFAULT_RESPONSE_FORMAT;
    protected array $templates = [
        Config::DEFAULT_RESPONSE_FORMAT => Config::DEFAULT_TEMPLATE
    ];

    public function __construct(?PsrResponseInterface $psrResponse = null)
    {
        $psr17Factory = new Psr17Factory();
        $this->setPsrResponse(psrResponse: match ($psrResponse) {
            null => $psr17Factory->createResponse(),
            default => $psrResponse
        });
    }
    static public function fromPsr(psrResponseInterface $psrResponse): static {
        return new self(psrResponse: $psrResponse);
    }
    public function setPsrResponse(PsrResponseInterface $psrResponse): static
    {
        $this->psrResponse = $psrResponse;
        return $this;
    }
    public function getPsrResponse(): PsrResponseInterface
    {
        return $this->psrResponse;
    }
    public function setResponseFormat(string $format): bool {
        if (!\in_array(needle: $format, haystack: \array_keys(Config::RESPONSE_RENDERERS)) || empty(Config::RESPONSE_RENDERERS[$format])) {
            return false;
        }
        $this->responseFormat = $format;
        return true;
    }
    public function getResponseFormat(): string
    {
        return $this->responseFormat;
    }
    public function setTemplate(string $template, ?string $format = null): bool
    {
        $format ??= Config::DEFAULT_RESPONSE_FORMAT;
        if (!\in_array(needle: $format, haystack: \array_keys(Config::RESPONSE_RENDERERS)) || empty(Config::RESPONSE_RENDERERS[$format])) {
            return false;
        }
        $this->templates[$format] = $template;
        return true;
    }
    public function getTemplate(?string $format = null): string
    {
        return $this->templates[$format ?? Config::DEFAULT_RESPONSE_FORMAT] ?? Config::DEFAULT_TEMPLATE;
    }
    public function addBodyFragment(mixed $part): void {
        $this->bodyFragments[] = $part;
    }
    public function getBodyFragments(): array {
        return $this->bodyFragments;
    }
    public function addHeader(string $name, $value): void {
        $this->setPsrResponse(psrResponse: $this->psrResponse->withHeader($name, $value));
    }
    public function removeHeader(string $name): void {
        $this->setPsrResponse(psrResponse: $this->psrResponse->withoutHeader($name));
    }

    // Wrappers around PSR

    // MessageInterface
    public function getProtocolVersion(): string
    {
        return $this->psrResponse->getProtocolVersion();
    }
    public function withProtocolVersion(string $version): static
    {
        $new = clone $this;
        $new->setPsrResponse(psrResponse: $this->psrResponse->withProtocolVersion($version));
        return $new;
    }
    public function getHeaders(): array
    {
        return $this->psrResponse->getHeaders();
    }
    public function hasHeader(string $name): bool
    {
        return $this->psrResponse->hasHeader($name);
    }
    public function getHeader(string $name): array
    {
        return $this->psrResponse->getHeader($name);
    }
    public function getHeaderLine(string $name): string
    {
        return $this->psrResponse->getHeaderLine($name);
    }
    public function withHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->setPsrResponse(psrResponse: $this->psrResponse->withHeader($name, $value));
        return $new;
    }
    public function withAddedHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->setPsrResponse(psrResponse: $this->psrResponse->withAddedHeader($name, $value));
        return $new;
    }
    public function withoutHeader(string $name): static
    {
        $new = clone $this;
        $new->setPsrResponse(psrResponse: $this->psrResponse->withoutHeader($name));
        return $new;
    }
    public function getBody(): PsrStreamInterface
    {
        return $this->psrResponse->getBody();
    }
    public function withBody(PsrStreamInterface $body): static
    {
        $new = clone $this;
        $new->setPsrResponse(psrResponse: $this->psrResponse->withBody($body));
        return $new;
    }
    public function getStatusCode(): int
    {
        return $this->psrResponse->getStatusCode();
    }
    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $new = clone $this;
        $new->setPsrResponse(psrResponse: $this->psrResponse->withStatus($code, $reasonPhrase));
        return $new;
    }
    public function getReasonPhrase(): string
    {
        return $this->psrResponse->getReasonPhrase();
    }
}