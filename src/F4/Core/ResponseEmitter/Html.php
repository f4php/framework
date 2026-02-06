<?php

declare(strict_types=1);

namespace F4\Core\ResponseEmitter;

use ErrorException;

use F4\Config;
use F4\Core\Exception\HttpException;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\SensitiveParameterAwareTrait;
use F4\HookManager;

use F4\Core\ResponseEmitter\AbstractResponseEmitter;
use F4\Core\ResponseEmitter\ResponseEmitterInterface;

use F4\Core\Phug\TemplateRenderer;

use function date;
use function date_default_timezone_get;
use function get_class;

class Html extends AbstractResponseEmitter implements ResponseEmitterInterface
{
    use SensitiveParameterAwareTrait;
    public function emit(ResponseInterface $response, ?RequestInterface $request = null): bool
    {
        if ($exception = $response->getException()) {
            $code = match (($code = $exception->getCode()) >= 400) {
                true => $code,
                default => 500
            };
            $response = $response->withStatus($code, HttpException::PHRASES[$code] ?? 'Internal Server Error');
        }
        $response = $response->withHeader('Content-Type', "text/html; charset=" . Config::RESPONSE_CHARSET);
        $timestamp = time();
        $data = [
            'config' => $this->getClassConstantsWithoutSensitive(className: Config::class),
            'locale' => $this->f4->getLocalizer()->getLocale(),
            'request' => [
                'path' => $request->getPath(),
                'headers' => $request->getHeaders(),
                'method' => $request->getMethod(),
                'parameters' => $request->getParameters(),
                'validated-parameters' => $request->getValidatedParameters(),
            ],
            'response' => [
                'data' => $response->getData(),
                'datetime' => date('c', $timestamp),
                'exception' => match ($exception = $response->getException()) {
                    null => null,
                    default => [
                        'code' => $exception->getCode(),
                        'message' => $exception->getMessage(),
                        'type' => get_class($exception),
                    ]
                },
                'headers' => $response->getHeaders(),
                'meta' => $response->getMetaData(),
                'timezone' => Config::TIMEZONE ?: date_default_timezone_get(),
            ],
            't' => $this->f4->getLocalizer()->getTranslateFunction(),
        ];
        HookManager::triggerHook(HookManager::AFTER_TEMPLATE_CONTEXT_READY, [
            'context' => $data
        ]);
        $pugRenderer = new TemplateRenderer([
            'f4' => $this->f4
        ]);
        parent::emitHeaders($response);
        if (parent::shouldEmitBody($response)) {
            if (empty($template = $response->getTemplate())) {
                throw new ErrorException(message: 'renderer template missing');
            }
            $pugRenderer->displayFile(file: $template, args: $data);
        }
        return true;
    }

}
