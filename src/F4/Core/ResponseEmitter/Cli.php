<?php

declare(strict_types=1);

namespace F4\Core\ResponseEmitter;

use ErrorException;

use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\ResponseEmitter\AbstractResponseEmitter;
use F4\Core\ResponseEmitter\ResponseEmitterInterface;

use function php_sapi_name;

class Cli extends AbstractResponseEmitter implements ResponseEmitterInterface
{
    public const string INTERNAL_MIME_TYPE = 'application/x.f4.cli';

    public function emit(ResponseInterface $response, ?RequestInterface $request = null): bool
    {
        if (php_sapi_name() !== 'cli') {
            throw new ErrorException('This emitter is designed for command-line environment only');
        }
        $this->emitBody($response);
        return true;
    }

}
