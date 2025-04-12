<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\Exception\HttpException;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ResponseInterface extends PsrResponseInterface
{
    public function setPsrResponse(PsrResponseInterface $psrResponse): self;
    public function getPsrResponse(): PsrResponseInterface;
    public function setResponseFormat(string $format): static;
    public function getResponseFormat(): string;
    public function setTemplate(string $template, ?string $format = null): static;
    public function getTemplate(?string $format = null): ?string;
    public function setMetaData(string $name, mixed $fragment): static;
    public function getMetaData(): array;
    public function setException(HttpException $exception): static;
    public function getException(): ?HttpException;
    public function setData(mixed $data): static;
    public function getData(): mixed;
    public function withRedirect(string $location, bool $permanent = false): static;
    public function withPermanentRedirect(string $location): static;
    public static function fromPsr(psrResponseInterface $psrResponse): self;
}