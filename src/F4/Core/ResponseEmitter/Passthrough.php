<?php

declare(strict_types=1);

namespace F4\Core\ResponseEmitter;

use F4\Core\ResponseEmitter\AbstractResponseEmitter;
use F4\Core\ResponseEmitter\ResponseEmitterInterface;

class Passthrough extends AbstractResponseEmitter implements ResponseEmitterInterface
{
    public const string INTERNAL_MIME_TYPE = 'application/x.f4.passthrough';
}
