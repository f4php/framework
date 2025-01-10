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
    public function getTemplate(?string $format = null): string;
    public function addMetaData(mixed $fragment): void;
    public function getMetaData(): array;
    public function setException(HttpException $exception): void;
    public function getException(): ?HttpException;
    public function setData(mixed $data): static;
    public function getData(): mixed;
    static public function fromPsr(psrResponseInterface $psrResponse): self;
}