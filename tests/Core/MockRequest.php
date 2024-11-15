<?php

declare(strict_types=1);

namespace F4\Tests\Core;

use F4\Core\Request;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

class MockRequest extends Request {

    public function __construct(string $requestMethod, string $requestPath, string $queryString=null) { 
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );
        parse_str(string: $queryString ?? '', result: $queryParams);
        parent::__construct(psrRequest: $creator->fromArrays([
            'REQUEST_METHOD' => $requestMethod,
            'REQUEST_URI' => "{$requestPath}?{$queryString}",
            'QUERY_STRING' => $queryString,
        ], [], [], $queryParams ?? [], null, [], null));
    }

}
