<?php

declare(strict_types=1);

namespace F4\Core\Debugger;

use Throwable;

use F4\Core\Debugger\ExportResult;

use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame as BacktraceFrame;

use function array_map;
use function array_slice;
use function file_get_contents;
use function implode;
use function max;
use function min;
use function mb_strlen;
use function mb_substr;
use function preg_match;

class BacktraceResult
{
    final public function __construct(protected Throwable $exception) {}
    public static function fromThrowable(Throwable $exception): static
    {
        return new static($exception);
    }
    public function toArray(): array
    {
        $backtrace = Backtrace::createForThrowable($this->exception)->withArguments();
        $lineOffset = -2;
        return array_map(function (BacktraceFrame $frame) use ($lineOffset): array {
            return [
                'file' => match ($frame->file === 'unknown') {
                    true => null,
                    default => $frame->file
                },
                'line' => $frame->lineNumber,
                'offset' => $lineOffset,
                // TODO: This causes recursion loop
                // 'arguments' => ExportResult::fromVariable($frame->arguments)->toArray(),
                'class' => $frame->class,
                'method' => $frame->method,
                'vendor' => !$frame->applicationFrame,
                'source' => match ($frame->file === 'unknown' && $frame->lineNumber === 0) {
                    true => null,
                    default => self::fetchSourceCode($frame->file, $frame->lineNumber + $lineOffset)
                }
            ];
        }, $backtrace->frames());
    }
    private static function fetchSourceCode(string $file, int $line, int $length = 20, bool $stripLeadingWhitespace = true): string
    {
        $file = file_get_contents(filename: $file);
        $lines = array_slice(array: explode(separator: "\n", string: $file), offset: max(0, $line - 1), length: $length);
        if ($stripLeadingWhitespace) {
            $lines = array_map(fn($line) =>
                mb_substr(
                    $line,
                    min(
                        array_map(
                            fn($l) =>
                            mb_strlen(preg_match('/^( +)/u', $l, $m) ? $m[1] : ''),
                            array_filter($lines, fn($l) => $l) // ignore empty lines
                        ),
                    ),
                ), $lines);
        }
        return implode("\n", $lines);
    }
}
