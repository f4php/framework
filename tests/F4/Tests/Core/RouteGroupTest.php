<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\Route;
use F4\Core\RouteGroup;

use F4\Tests\Core\MockRequest;
use F4\Tests\Core\MockResponse;
use F4\Tests\TestException;


use Exception;
use InvalidArgumentException;
use Throwable;

final class RouteGroupTest extends TestCase
{
    public function testSimpleRoutePathMatching(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = 'GET /';
        $routeGroup = (new RouteGroup())
            ->addRoutes([
                new Route($routePathDefinition, function (): string {
                    return 'test-value-1';
                }),
            ]);
        $results = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $results);
    }

    public function testStaticCreationGet(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routeGroup = RouteGroup::withRoutes(
            Route::get('/', function (): string {
                return 'test-value-1';
            }),
        );
        $results = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $results);
    }
    public function testStaticCreationAny(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routeGroup = RouteGroup::withRoutes(
            Route::any('/', function (): string {
                return 'test-value-2';
            }),
        );
        $results = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-2', $results);
    }
    public function testRequestHandlers(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = 'GET /';

        $route = new Route($routePathDefinition, function () use (&$route): string {
            return $route->getState('test');
        });

        $routeGroup = RouteGroup::withRoutes(
            $route
                ->before(function (RequestInterface $request, ResponseInterface $response, Route $route) {
                    $route->setState('test', $request->getHeaderLine('X-Test-Header'));
                }),
        )
            ->before(function (RequestInterface $request, ResponseInterface $response, ?RouteGroup $routeGroup) {
                return $request->withHeader('X-Test-Header', 'test-value-1');
            });
        $result = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $result);
    }
    public function testResponseHandlers(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = 'GET /';
        $route = new Route($routePathDefinition, function () use (&$route): void{
            $route->setState('test', 'test-value-1');
        });
        $routeGroup = RouteGroup::withRoutes(
            $route
                ->after(function (ResponseInterface $response, RequestInterface $request, ?Route $route) {
                    return $response->withHeader('X-Test-Header', $route->getState('test'));
                }),
        )
            ->after(function (ResponseInterface $response, RequestInterface $request, ?RouteGroup $routeGroup) {
                return $response->withHeader('X-Test-Header-2', $response->getHeaderLine('X-Test-Header'));
            });
        $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $response->getHeaderLine('X-Test-Header-2'));
    }
    public function testExceptionHandling(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = 'GET /';
        $routeGroup = RouteGroup::withRoutes(
            (new Route($routePathDefinition, function (): string{
                throw new TestException('test');
            }))
                ->on(TestException::class, function (Throwable $exception, RequestInterface $request, ResponseInterface $response, ?Route $route) {
                    return 'test-value-1';
                }),
        );
        $result = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $result);
    }
    public function testInvalidMultipleExceptionHandling(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $requestMethod = 'GET';
        $requestPath = '/';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = 'GET /';
        $routeGroup = RouteGroup::withRoutes(
            new Route($routePathDefinition, function (): string {
                throw new TestException('test');
            }),
        )
            ->on(TestException::class, function (Throwable $exception, RequestInterface $request, ResponseInterface $response, ?Route $route) {
                return 'test-value-1';
            })
            ->on(TestException::class, function (Throwable $exception, RequestInterface $request, ResponseInterface $response, ?Route $route) {
                return 'test-value-2';
            })
        ;
        $result = $routeGroup->invoke(request: $request, response: $response);
    }
    public function testPathPrefix(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/group/route';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routeGroupPathPrefix = '/group';
        $routePathDefinition = 'GET /route';
        $routeGroup1 = new RouteGroup($routeGroupPathPrefix, [
            new Route($routePathDefinition, function (): string {
                return 'test 1';
            })
        ]);
        $routeGroup2 = RouteGroup::fromRoutes(
            new Route($routePathDefinition, function (): string {
                return 'test 2';
            }),
        )
            ->setPathPrefix($routeGroupPathPrefix);
        $routeGroup3 = RouteGroup::fromRoutes(
            new Route('GET /group', function (): string {
                return 'test 3';
            }),
        )
            ->setPathPrefix($routeGroupPathPrefix);
        $this->assertSame('test 1', $routeGroup1->invoke(request: $request, response: $response));
        $this->assertSame('test 2', $routeGroup2->invoke(request: $request, response: $response));
        $this->assertSame(null, $routeGroup3->invoke(request: $request, response: $response));
    }
    public function testEmptyPathWithPrefix(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/group';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routeGroupPathPrefix = '/group';
        $routeGroup1 = new RouteGroup($routeGroupPathPrefix, [
            new Route('', function (): string {
                return 'test 1';
            })
        ]);
        $this->assertSame('test 1', $routeGroup1->invoke(request: $request, response: $response));
    }
}