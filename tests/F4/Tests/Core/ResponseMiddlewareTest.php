<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\ResponseMiddleware;

final class ResponseMiddlewareTest extends TestCase
{
    public function testInvoke(): void
    {
        $middleware = new ResponseMiddleware(function(ResponseInterface $response, RequestInterface $request): string {
            return $response->getHeaderLine('X-Test-Header');
        });
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $response = (new MockResponse())
            ->withHeader(name: 'X-Test-Header', value: 'test-value');
        $this->assertSame('test-value', $middleware->invoke(response: $response, request: $request, context: $this));
    }

}