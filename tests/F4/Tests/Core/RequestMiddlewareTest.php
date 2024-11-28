<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Core\RequestInterface;
use F4\Core\RequestMiddleware;
use F4\Core\ResponseInterface;

final class RequestMiddlewareTest extends TestCase
{
    public function testInvoke(): void
    {
        $middleware = new RequestMiddleware(function(RequestInterface $request, ResponseInterface$response): string {
            return $request->getHeaderLine('X-Test-Header');
        });
        $request = (new MockRequest(requestMethod: 'GET', requestPath: '/'))
            ->withHeader(name: 'X-Test-Header', value: 'test-value');
        $response = new MockResponse();
        $this->assertSame('test-value', $middleware->invoke(request: $request, response: $response, context: $this));
    }

}