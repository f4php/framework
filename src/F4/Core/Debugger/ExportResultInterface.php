<?php

declare(strict_types=1);

namespace F4\Core\Debugger;

interface ExportResultInterface
{
    public static function fromVariable(mixed $variable, ?string $name = null, mixed $meta = null): static;
    public function getPreview(): string;
    public function toArray(): array;
}
