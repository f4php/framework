<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Core\Route;
use F4\Core\RouteGroup;

use F4\Tests\Core\MockRequest;
use F4\Tests\Core\MockResponse;

use InvalidArgumentException;
use TypeError;

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
        
        $routePathDefinition = 'GET /';
        $routeGroup = RouteGroup::withRoutes([
                Route::get('/', function (): string {
                        return 'test-value-1';
                }),
                Route::any('/', function (): string {
                        return 'test-value-2';
                }),
            ]);
        $results = $routeGroup->invoke(request: $request, response: $response);
        $this->assertSame('test-value-1', $results[0]);
        $this->assertSame('test-value-2', $results[1]);
    }
    // public function testRequestHandlers(): void
    // {
    //     $requestMethod = 'GET';
    //     $requestPath = '/';
    //     $responseFormat = 'text/html';

    //     $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
    //     $response = new MockResponse(responseFormat: $responseFormat);
        
    //     $routePathDefinition = 'GET /';
    //     $routeGroup = (new RouteGroup())
    //         ->addRoutes([
    //             (new Route($routePathDefinition, function (): void {
    //                     // return 'test-value-1';
    //             }))
    //                 ->before(function($request, $response) {
    //                     $this->state['x-test-header'] = $request->getHeaderLine()
    //                 },
    //             (new Route($routePathDefinition, function (): void {
    //                     // return 'test-value-2';
    //             })),
    //         ])
    //         ->before(function($request, $response) {
    //             $response->withHeader('X-Test-Header', 'test-value-1');
    //         });
    //     $results = $routeGroup->invoke(request: $request, response: $response);
    //     $this->assertSame('test-value-1', $response->getHeaderLine('X-Test-Header'));
    // }
}