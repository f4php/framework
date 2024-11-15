<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Core;
use F4\Core\CoreApiInterface;
use F4\Core\Request;
use F4\Core\Response;
use F4\Core\Route;
use F4\Core\Router;
use F4\ModuleInterface;

use F4\Tests\TestException;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

final class RouterTest extends TestCase
{
    public function testRoutePriorities(): void
    {
        $router = new Router();
        $requestMethod = 'GET';
        $requestPath = '/';
        $request = new MockRequest($requestMethod, $requestPath);
        $response = new MockResponse();
        $routePathDefinition = 'GET /';
        $router->addRoute($routePathDefinition, function (): string {
            return 'E';
        })
            ->setPriority(Route::PRIORITY_LOW);
        $router->addRoute($routePathDefinition, function (): string {
            return 'B';
        })
            ->setPriority(Route::PRIORITY_HIGH);
        $router->addRoute($routePathDefinition, function (): string {
            return 'C';
        })
            ->setPriority(Route::PRIORITY_NORMAL);
        $router->addRoute($routePathDefinition, function (): string {
            return 'A';
        })
            ->setPriority(Route::PRIORITY_CRITICAL);
        // default priority is PRIORITY_NORMAL
        $router->addRoute(new Route($routePathDefinition, function (): string {
            return 'D';
        }));
        $result = $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame('A', $result[0]);
        $this->assertSame('B', $result[1]);
        $this->assertSame('C', $result[2]);
        $this->assertSame('D', $result[3]);
        $this->assertSame('E', $result[4]);
    }

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
        $router->addRoute(new Route($routePathDefinition, $handler));
        $result = $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame(123, $result[0][0]);
        $this->assertSame(234, $result[0][1]);
        $this->assertSame(1, $result[0][2]);
        $this->assertSame(123, $result[1][0]);
        $this->assertSame(234, $result[1][1]);
        $this->assertSame(1, $result[1][2]);
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
        $router->addRoute($routePathDefinition, function (): void {})
            ->before(function ($r) use (&$request): void {
                $request = $r->withHeader('X-Test-Request-Header', 'request-test-value');
            });
        $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame('request-test-value', $request->getHeaderLine('X-Test-Request-Header'));
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
        $router->addRoute($routePathDefinition, function (): void {})
            ->after(function ($r) use (&$response): void {
                $response = $r->withHeader('X-Test-Response-Header', 'response-test-value');
            });
        $router->invokeMatchingRoutes(request: $request, response: $response);
        $this->assertSame('response-test-value', $response->getHeaderLine('X-Test-Response-Header'));
    }

}