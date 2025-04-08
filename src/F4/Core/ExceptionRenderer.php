<?php

declare(strict_types=1);

namespace F4\Core;

use Throwable;

use F4\Config;
use F4\Core\Phug\TemplateRenderer as PhugTemplateRenderer;


use F4\Core\Debugger\BacktraceResult;

use function get_class;
use function header;
use function json_encode;
use function ob_end_clean;
use function php_sapi_name;
use function restore_error_handler;
use function restore_exception_handler;

class ExceptionRenderer
{

    public static function handleException(Throwable $exception, ?string $format = null): void
    {
        restore_error_handler();
        restore_exception_handler();
        @ob_end_clean();
        match (php_sapi_name()) {
            'cli' => self::asConsoleText(exception: $exception),
            default => match ($format) {
                    'text/html' => self::asHtml(exception: $exception),
                    'application/json' => self::asJson(exception: $exception),
                    default => self::asText(exception: $exception),
                }
        };
    }
    public static function asHtml(Throwable $exception): never
    {
        try {
            // static::sendCommonHttpHeaders(exception: $exception);
            header(sprintf(
                '%s: %s',
                'Content-Type',
                "text/html; charset=" . Config::RESPONSE_CHARSET,
            ), true, 500);
            // $file = file_get_contents(filename: $exception->getFile());
            // $lines = array_slice(array: explode(separator: "\n", string: $file), offset: \max(0, $exception->getLine() - 10), length: 20);
            $data = [
                'exception' => [
                    'class' => get_class($exception),
                    'code' => $exception->getCode() ?: 500,
                    'message' => $exception->getMessage(),
                    ...match (Config::DEBUG_MODE) {
                        true => [
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                            'trace' => BacktraceResult::fromThrowable($exception)->toArray()
                        ],
                        default => []
                    }
                ]
                ,

                // 'config' => [],
                // 'request' => [],
                // 'response' => [],
                // 'exception' => [
                //     'code' => $exception->getCode(),
                //     'class' => get_class($exception),
                //     'message' => $exception->getMessage(),
                // ],
                // 'meta' => match(Config::DEBUG_MODE) {
                //     true => [
                //         'debug' => true,
                //         'source' => $lines,
                //         'file' => $exception->getFile(),
                //         'line' => $exception->getLine(),
                //         'line-offset' => max(0, $exception->getLine() - 10),
                //         'trace' => $exception->getTraceAsString(),
                //     ],
                //     default => []
                // }
            ];
            $pug = new PhugTemplateRenderer();
            echo $pug->displayFile(__DIR__ . '/../../../templates/exception/exception.pug', $data);
        } catch (Throwable $e) {
            header(header: "Content-Type: text/plain; charset=" . Config::RESPONSE_CHARSET);
            echo $e->getMessage();
            if (Config::DEBUG_MODE) {
                echo $e->getTraceAsString();
            }
        }
        exit();
    }
    public static function asJson(Throwable $exception): never
    {
        header(sprintf(
            '%s: %s',
            'Content-Type',
            "application/json; charset=" . Config::RESPONSE_CHARSET,
        ), true, 500);
        $data = [
            'error' => $exception->getMessage(),
            'type' => get_class(object: $exception),
            'code' => $exception->getCode()
        ];
        if (Config::DEBUG_MODE) {
            $data['trace'] = $exception->getTrace();
        }
        echo json_encode(value: $data);
        exit();
    }
    public static function asText(Throwable $exception): never
    {
        header(sprintf(
            '%s: %s',
            'Content-Type',
            "text/plain; charset=" . Config::RESPONSE_CHARSET,
        ), true, 500);
        echo get_class(object: $exception) . ": " . $exception->getMessage() . "\n";
        if (Config::DEBUG_MODE) {
            echo $exception->getTraceAsString() . "\n";
        }
        exit();
    }
    public static function asConsoleText(Throwable $exception): never
    {
        echo get_class(object: $exception) . ": " . $exception->getMessage() . "\n";
        if (Config::DEBUG_MODE) {
            echo $exception->getTraceAsString() . "\n";
        }
        exit(1);
    }
}
