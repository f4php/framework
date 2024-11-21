<?php

declare(strict_types=1);

namespace F4\Core;

use Throwable;

use F4\Config;
use F4\Core\Phug\TemplateRenderer as PhugTemplateRenderer;

class ExceptionRenderer
{
    public static function handleException(Throwable $exception, ?string $format = null): void {
        \restore_error_handler();
        \restore_exception_handler();
        @\ob_end_clean();
        match(\php_sapi_name()) {
            'cli' => self::asConsoleText(exception: $exception),
            default => match ($format) {
                'text/html' => self::asHtml(exception: $exception),
                'application/json' => self::asJson(exception: $exception),
                default => self::asText(exception: $exception),
            }
        };
    }

    public static function asHtml(Throwable $exception): never {
        static::sendCommonHttpHeaders(exception: $exception);
        \header(header: "Content-Type: text/html; charset=".Config::RESPONSE_CHARSET);
        $file = \file_get_contents(filename: $exception->getFile());
        $lines = \array_slice(array: \explode(separator: "\n", string: $file), offset: \max(0, $exception->getLine() - 10), length: 20);
        $data = [
            'exception'=> $exception,
            'debug'=> Config::DEBUG_MODE && Config::DEBUG_EXTENDED_ERROR_OUTPUT,
            'source' => $lines,
            'line' => $exception->getLine(),
            'line-offset' => \max(0, $exception->getLine() - 10)
        ];
        $pug = new PhugTemplateRenderer();
        echo $pug->renderTemplate(file: __DIR__.'/../../../templates/exception/index.pug', args: ['data'=>$data]);
        exit();
    }

    public static function asJson(Throwable $exception): never {
        static::sendCommonHttpHeaders(exception: $exception);
        \header(header: "Content-Type: application/json; charset=".Config::RESPONSE_CHARSET);
        $data = [
            'error' => $exception->getMessage(),
            'type'  => \get_class(object: $exception),
            'code'  => $exception->getCode()
        ];
        if(Config::DEBUG_MODE && Config::DEBUG_EXTENDED_ERROR_OUTPUT) {
            $data['trace'] = $exception->getTrace();
        }
        echo \json_encode(value: $data);
        exit();
    }

    public static function asText(Throwable $exception): never {
        static::sendCommonHttpHeaders(exception: $exception);
        \header(header: "Content-Type: text/plain; charset=".Config::RESPONSE_CHARSET);
        echo \get_class(object: $exception). ": ".$exception->getMessage() . "\n";
        if(Config::DEBUG_MODE && Config::DEBUG_EXTENDED_ERROR_OUTPUT) {
            echo $exception->getTraceAsString()."\n";
        }
        exit();
    }

    public static function asConsoleText(Throwable $exception): never {
        echo \get_class(object: $exception). ": ".$exception->getMessage() . "\n";
        if(Config::DEBUG_MODE && Config::DEBUG_EXTENDED_ERROR_OUTPUT) {
            echo $exception->getTraceAsString()."\n";
        }
        exit(1);
    }

    protected static function sendCommonHttpHeaders(Throwable $exception): void {
        @\header(header: "HTTP/1.0 500 Internal Server Error");
    }

}
