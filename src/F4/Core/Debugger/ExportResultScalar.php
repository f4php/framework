<?php

declare(strict_types=1);

namespace F4\Core\Debugger;

use F4\Core\Debugger\ExportResultInterface;

use function gettype;
use function sprintf;

class ExportResultScalar implements ExportResultInterface
{
    protected bool $complex = false;
    public function __construct(protected ?string $name = null, protected ?string $type = null, protected ?string $preview = null, protected mixed $value = null, protected mixed $meta = null) {}

    public static function fromVariable(mixed $variable, ?string $name = null, mixed $meta = null): static
    {
        $type = gettype($variable);
        $preview = static::generatePreview($variable, $name);
        $value = static::generateValue($variable, $name);
        /** 
         * @phpstan-ignore new.static
         */
        return new static($name, $type, $preview, $value, $meta);
    }
    protected static function generatePreview(mixed $variable, ?string $name = null): string
    {
        $type = gettype($variable);
        return match ($type === 'integer' || $type === 'double') {
            true => (string) $variable,
            default => match ($variable === null) {
                    true => 'null',
                    default => match ($type === 'boolean') {
                            true => $variable ? 'true' : 'false',
                            default => sprintf('"%s"', $variable)
                        }
                }
        };
    }
    protected static function generateValue(mixed $variable, ?string $name = null): mixed
    {
        return match ($variable === null) {
            true => null,
            default => self::generatePreview($variable)
        };
    }
    public function getPreview(): string
    {
        return $this->preview;
    }
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'preview' => $this->preview,
            'complex' => $this->complex,
            'meta' => $this->meta,
            'value' => $this->value,
        ];
    }
}
