<?php

declare(strict_types=1);

namespace F4\Core;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;

interface RequestInterface extends PsrServerRequestInterface
{
    public function getPath(): string;
    public function getExtension(): ?string;
    public function getDebugExtension(): ?string;
    public function getLanguage(): ?string;
    public function setPsrRequest(PsrServerRequestInterface $psrRequest): self;
    public function getPsrRequest(): PsrServerRequestInterface;
    static public function fromPsr(psrServerRequestInterface $psrRequest): self;
    public function setParameters(array $parameters): static;
    public function getParameters(): mixed;
    public function setValidatedParameters(array $parameters): static;
    public function getValidatedParameters(): mixed;
}