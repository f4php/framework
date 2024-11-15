<?php

declare(strict_types=1);

namespace F4\Tests\Core;

use F4\Core\Response;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

class MockResponse extends Response {

    public function __construct(?string $responseFormat = null) { 
        $psr17Factory = new Psr17Factory();
        parent::__construct(psrResponse: $psr17Factory->createResponse());
        if($responseFormat !== null) {
            $this->setResponseFormat(format: $responseFormat);
        }
    }

}
