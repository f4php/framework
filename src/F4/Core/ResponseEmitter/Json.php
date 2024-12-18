<?php

declare(strict_types=1);

namespace F4\Core\ResponseEmitter;

use F4\Config;
use F4\Core\Exception\HttpException;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\ResponseEmitter\AbstractResponseEmitter;
use F4\Core\ResponseEmitter\ResponseEmitterInterface;

use function get_class;

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
                'type' => get_class($exception),
                'code' => $code,
            ];
            if (Config::DEBUG_MODE === true) {
                $data['backtrace'] = $exception->getTrace();
            }
            $response = $response->withStatus($code, HttpException::PHRASES[$code] ?? 'Internal Server Error');
            $response->getBody()->write(json_encode($data));
            return parent::emit($response);
        }
        $response->getBody()->write(json_encode($response->getData()));
        return parent::emit($response);
    }

}
