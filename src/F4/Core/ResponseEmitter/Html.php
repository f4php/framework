<?php

declare(strict_types=1);

namespace F4\Core\ResponseEmitter;

use ErrorException;

use F4\Config;
use F4\Core\Exception\HttpException;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;

use F4\Core\ResponseEmitter\AbstractResponseEmitter;
use F4\Core\ResponseEmitter\ResponseEmitterInterface;

use F4\Core\Phug\TemplateRenderer as PhugTemplateRenderer;

class Html extends AbstractResponseEmitter implements ResponseEmitterInterface
{
    public function emit(ResponseInterface $response, ?RequestInterface $request = null): bool
    {
        if(empty($template = $response->getTemplate())) {
            throw new ErrorException(message: 'renderer template missing');
        }
        if($exception = $response->getException()) {
            $code = match(($code = $exception->getCode()) >= 400) {
                true => $code,
                default => 500
            };
            $response = $response->withStatus($code, HttpException::PHRASES[$code] ?? 'Internal Server Error');
        }

        $data = [
            'config' => [],
            'request' => [],
            'meta' => $response->getMetaData(),
            'response' => $response->getData(),
            'exception' => $response->getException(),
        ];
        $pugRenderer = new PhugTemplateRenderer();
        $response = $response->withHeader('Content-Type', "text/html; charset=" . Config::RESPONSE_CHARSET);
        $response->getBody()->write($pugRenderer->renderTemplate(file: $template, args: $data));
        return parent::emit($response);
    }

}
