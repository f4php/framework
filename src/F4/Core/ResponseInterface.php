<?php

declare(strict_types=1);

namespace F4\Core;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ResponseInterface extends PsrResponseInterface
{
    public function setPsrResponse(PsrResponseInterface $psrResponse): self;
    public function getPsrResponse(): PsrResponseInterface;
    public function setResponseFormat(string $format): bool;
    public function getResponseFormat(): string;
    public function setTemplate(string $template, string $format = null): bool;
    public function getTemplate(?string $format = null): string;
    public function addPartialResult(mixed $part): void;
    public function getPartialResults(): array;
    public function addHeader(string $name, $value): void;
    public function removeHeader(string $name): void;
    static public function fromPsr(psrResponseInterface $psrResponse): self;

}