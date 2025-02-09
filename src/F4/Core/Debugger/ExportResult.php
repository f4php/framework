<?php

declare(strict_types=1);

namespace F4\Core\Debugger;

use F4\Core\Debugger\ExportResultArray;
use F4\Core\Debugger\ExportResultClosure;
use F4\Core\Debugger\ExportResultInterface;
use F4\Core\Debugger\ExportResultObject;
use F4\Core\Debugger\ExportResultResource;

use Closure;

use function gettype;

class ExportResult implements ExportResultInterface
{
    protected ExportResultInterface $exportResult;
    public function __construct(mixed $variable, ?string $name = null, mixed $meta = null)
    {
        switch ($type = gettype($variable)) {
            case 'array':
                $this->exportResult = ExportResultArray::fromVariable($variable, $name, $meta);
                break;
            case 'object':
                $this->exportResult = ($variable instanceof Closure) ? ExportResultClosure::fromVariable($variable, $name, $meta) : ExportResultObject::fromVariable($variable, $name, $meta);
                break;
            case 'resource':
            case 'resource (closed)':
                $this->exportResult = ExportResultResource::fromVariable($variable, $name, $meta);
                break;
            default:
                $this->exportResult = ExportResultScalar::fromVariable($variable, $name, $meta);
        }
    }
    public static function fromVariable(mixed $variable, ?string $name = null, mixed $meta = null): static
    {
        return new self($variable, $name, $meta);
    }
    public function getPreview(): string
    {
        return $this->exportResult->getPreview();
    }
    public function toArray(): array
    {
        return $this->exportResult->toArray();
    }
}
