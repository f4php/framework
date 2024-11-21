<?php

declare(strict_types=1);

namespace F4\Core\ResponseEmitter;

use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;

interface ResponseEmitterInterface {
  public function emit(ResponseInterface $response, ?RequestInterface $request = null): bool;
}