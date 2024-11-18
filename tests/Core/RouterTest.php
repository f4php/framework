<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use ErrorException;
use InvalidArgumentException;

use F4\Core\Route;
use F4\Core\Router;

final class RouterTest extends TestCase
{
    public function testBasicRouteParams(): void
    {
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /entities/{entityID:int}';
        $handler = function (int $entityID = 0, int $param = 0, int $param2 = 1): array {
            return [$entityID, $param, $param2];
        };
        $router->addRoute($routePathDefinition, $handler);
        $result = $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame(123, $result[0]);
        $this->assertSame(234, $result[1]);
        $this->assertSame(1, $result[2]);
    }
    public function testBasicRouteParamsAltSyntax(): void
    {
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /entities/{entityID:int}';
        $handler = function (int $entityID = 0, int $param = 0, int $param2 = 1): array {
            return [$entityID, $param, $param2];
        };
        $router->addRoute(new Route($routePathDefinition, $handler));
        $result = $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame(123, $result[0]);
        $this->assertSame(234, $result[1]);
        $this->assertSame(1, $result[2]);
    }

    // TODO: rework this
    // public function testRequestMiddleware(): void
    // {
    //     $router = new Router();
    //     $requestMethod = 'GET';
    //     $queryString = 'entityID=345&param=234';
    //     $requestPath = "/entities/123";
    //     $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
    //     $response = new MockResponse();
    //     $routePathDefinition = 'GET /entities/{entityID:int}';
    //     $router->addRoute($routePathDefinition, function (): void {})
    //         ->before(function ($r) use (&$request): void {
    //             $request = $r->withHeader('X-Test-Request-Header', 'request-test-value');
    //         });
    //     $router->invokeMatchingRoutes(request: $request, response: $response);
    //     $this->assertSame('request-test-value', $request->getHeaderLine('X-Test-Request-Header'));
    // }

    // TODO: rework this
    // public function testResponseMiddleware(): void
    // {
    //     $router = new Router();
    //     $requestMethod = 'GET';
    //     $queryString = 'entityID=345&param=234';
    //     $requestPath = "/entities/123";
    //     $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
    //     $response = new MockResponse();
    //     $routePathDefinition = 'GET /entities/{entityID:int}';
    //     $router->addRoute($routePathDefinition, function (): void {})
    //         ->after(function ($r) use (&$response): void {
    //             $response = $r->withHeader('X-Test-Response-Header', 'response-test-value');
    //         });
    //     $router->invokeMatchingRoutes(request: $request, response: $response);
    //     $this->assertSame('response-test-value', $response->getHeaderLine('X-Test-Response-Header'));
    // }

    public function testErrorOnMultipleMatchingRoutes(): void
    {
        $this->expectException(ErrorException::class);
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /entities/{entityID:int}';
        $router->addRoute($routePathDefinition, function (): void {});
        $router->addRoute($routePathDefinition, function (): void {});
        $router->invokeMatchingRoutes(request: $request, response: $response);
    }
    public function testRequestMiddlewareAlreadySet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /entities/{entityID:int}';
        $router->before(function ($request, $response): void {});
        $router->setRequestMiddleware(function ($request, $response): void {});
    }
    public function testResponseMiddlewareAlreadySet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /entities/{entityID:int}';
        $router->after(function ($request, $response): void {});
        $router->setResponseMiddleware(function ($response, $request): void {});
    }

}