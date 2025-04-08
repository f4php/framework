<?php

declare(strict_types=1);

namespace F4\Core\ResponseEmitter;

use F4\Config;
use F4\Core\Exception\HttpException;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\ResponseEmitter\AbstractResponseEmitter;
use F4\Core\ResponseEmitter\ResponseEmitterInterface;

use function json_encode;

class Json extends AbstractResponseEmitter implements ResponseEmitterInterface
{
    public function emit(ResponseInterface $response, ?RequestInterface $request = null): bool
    {
        $response = $response->withHeader('Content-Type', "application/json; charset=" . Config::RESPONSE_CHARSET);
        if ($exception = $response->getException()) {
            $code = match (($code = $exception->getCode()) >= 400) {
                true => $code,
                default => 500
            };
            $data = [
                'error' => $exception->getMessage(),
                'code' => $code,
            ];
            $response = $response->withStatus($code, HttpException::PHRASES[$code] ?? 'Internal Server Error');
            $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            return parent::emit($response);
        }
        $response->getBody()->write(json_encode($response->getData(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return parent::emit($response);
    }

}
