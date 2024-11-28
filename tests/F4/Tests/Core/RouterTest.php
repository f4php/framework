<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use ErrorException;
use InvalidArgumentException;

use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\Route;
use F4\Core\Router;

use F4\Tests\TestException;

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
    public function testRequestMiddleware(): void
    {
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /entities/{entityID:int}';
        $router
            ->before(function ($request, $response, $route): RequestInterface {
                /**
                 * @var Route $this
                 */
                $route->setState('test', 'test value 2');
                $this->setState('test2', 'test value 3');
                return $request->withHeader('X-Test-Header', 'test value');
            });
        $router->addRoute($routePathDefinition, function (): array {
            /**
             * @var Route $this
             */
            return [
                $this->getState('test'),
                $this->getState('test2'),
            ];
        });
        $result = $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame('test value', $request->getHeaderLine('X-Test-Header'));
        $this->assertSame('test value 2', $result[0]);
        $this->assertSame('test value 3', $result[1]);
    }
    public function testResponseMiddleware(): void
    {
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /entities/{entityID:int}';
        $router
            ->after(function ($response, $request, $route): ResponseInterface {
                return $response->withHeader('X-Test-Header', 'test value');
            });
        $router->addRoute($routePathDefinition, function (): void {});
        $result = $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame('test value', $response->getHeaderLine('X-Test-Header'));
    }
    public function testRequestMiddlewareNoMathingRoute(): void
    {
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /mismatching-route';
        $router
            ->before(function ($request, $response, $route): RequestInterface {
                return $request->withHeader('X-Test-Header', 'test value');
            });
        $router->addRoute($routePathDefinition, function (): array {
            throw new ErrorException(message: 'This code should be unreachable');
        });
        $result = $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame('test value', $request->getHeaderLine('X-Test-Header'));
    }
    public function testResponseMiddlewareNoMathingRoute(): void
    {
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /mismatching-route';
        $router
            ->after(function ($response, $request, $route): ResponseInterface {
                return $response->withHeader('X-Test-Header', 'test value');
            });
        $router->addRoute($routePathDefinition, function (): array {
            throw new ErrorException(message: 'This code should be unreachable');
        });
        $result = $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame('test value', $response->getHeaderLine('X-Test-Header'));
    }
    public function testRequestResponseIsolation(): void
    {
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /entities/{entityID:int}';
        $router
            ->before(function ($request, $response, $route): void {
                $request = $request->withHeader('X-Test-Header', 'test value');
            })
            ->after(function ($response, $request, $route): void {
                $response = $response->withHeader('X-Test-Header', 'test value 2');
            });
        $router->addRoute($routePathDefinition, function (): void {});
        $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame('', $request->getHeaderLine('X-Test-Header'));
        $this->assertSame('', $response->getHeaderLine('X-Test-Header'));
    }
   public function testExceptionHandling(): void
    {
        $router = new Router();
        $requestMethod = 'GET';
        $queryString = 'entityID=345&param=234';
        $requestPath = "/entities/123";
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = 'GET /entities/{entityID:int}';
        $router
            ->before(function($request, $response, $route) {
                /**
                 * @var Route $this
                 */
                return $route ? $route->setState('test', 'test-value') : '';
            })
            ->on(TestException::class, function ($exception, $request, $response, $route): string {
                return $exception->getMessage();
            });
        $router
            ->addRoute($routePathDefinition, function (): void {
                /**
                 * @var Route $this
                 */
                throw new TestException($this->getState('test'));
            });
        $result = $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame('test-value', $result);
    }

}