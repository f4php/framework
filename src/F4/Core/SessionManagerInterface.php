<?php

declare(strict_types=1);

namespace F4\Core;

interface SessionManagerInterface
{
    public function get(?string $key = null): mixed;
    public function getName(): string;
    public function getParameters(): array;
    public function regenerate(bool $deleteOldSession = false): bool;
    public function reset(): bool;
    public function set(string $key, mixed $value): mixed;
    public function setName(string $name): bool;
    public function setParameters(array $parameters): bool;
    public function start(array $options = []): bool;
}
