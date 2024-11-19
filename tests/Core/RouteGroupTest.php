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
                new Route($routePathDefinition, function (): string {
                        return 'test-value-2';
                }),
            ]);
        $results = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $results[0]);
        $this->assertSame('test-value-2', $results[1]);
    }

    public function testStaticCreation(): void
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
            Route::any('/', function (): string {
                    return 'test-value-2';
            }),
        );
        $results = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $results[0]);
        $this->assertSame('test-value-2', $results[1]);
    }
    public function testRequestHandlers(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);
        
        $routePathDefinition = 'GET /';
        $routeGroup = RouteGroup::withRoutes(
            (new Route($routePathDefinition, function (): string {
                /**
                 * @var Route $this
                 */
                return $this->getState('test');
            }))
                ->before(function(RequestInterface $request, ResponseInterface $response) {
                    /**
                     * @var Route $this
                     */
                    $this->setState('test', $request->getHeaderLine('X-Test-Header'));
                })
        )
        ->before(function(RequestInterface $request, ResponseInterface $response, ?Route $route) {
            return $request->withHeader('X-Test-Header', 'test-value-1');
        });
        $result = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $result[0]);
    }
    public function testResponseHandlers(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);
        
        $routePathDefinition = 'GET /';
        $routeGroup = RouteGroup::withRoutes(
            (new Route($routePathDefinition, function (): void {
                /**
                 * @var Route $this
                 */
                $this->setState('test', 'test-value-1');
            }))
                ->after(function(ResponseInterface $response, RequestInterface $request, ?Route $route) {
                    /**
                     * @var Route $this
                     */
                    return $response->withHeader('X-Test-Header', $route->getState('test'));
                })
        )
        ->after(function(ResponseInterface $response, RequestInterface $request, ?Route $route) {
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
            (new Route($routePathDefinition, function (): string {
                throw new TestException('test');
            }))
                ->on(TestException::class, function(Throwable $exception, RequestInterface $request, ResponseInterface $response, ?Route $route) {
                    return 'test-value-1';
                })
        );
        $result = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $result[0]);
    }
    public function testMultipleExceptionHandling(): void
    {
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
            new Route($routePathDefinition, function (): string {
                /**
                 * @var Route $this
                 */
                $this->setState('test', 'test-value-2');
                throw new Exception();
            })
        )
        ->on(TestException::class, function(Throwable $exception, RequestInterface $request, ResponseInterface $response, ?Route $route) {
            return 'test-value-1';
        })
        ->on(Exception::class, function(Throwable $exception, RequestInterface $request, ResponseInterface $response, ?Route $route) {
            return $route->getState('test');
        });
        $result = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $result[0]);
        $this->assertSame('test-value-2', $result[1]);
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
            })
        )
        ->on(TestException::class, function(Throwable $exception, RequestInterface $request, ResponseInterface $response, ?Route $route) {
            return 'test-value-1';
        })
        ->on(TestException::class, function(Throwable $exception, RequestInterface $request, ResponseInterface $response, ?Route $route) {
            return 'test-value-2';
        })
        ;
        $result = $routeGroup->invoke(request: $request, response: $response);
    }
}