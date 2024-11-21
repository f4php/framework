<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Core\Validator\CastInt;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Request;
use F4\Core\Response;
use F4\Core\Route;

use F4\Tests\TestException;
use F4\Tests\Core\MockRequest;
use F4\Tests\Core\MockResponse;

use InvalidArgumentException;
use TypeError;

final class RouteTest extends TestCase
{
    public function testSimpleRoutePathMatching(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/';
        $responseFormat = 'text/html';
        $routePathDefinition = 'GET /';
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);
        $route = new Route($routePathDefinition, function (): void {});
        $this->assertSame('^(GET)\s+\/$', $route->getRequestPathRegExp());
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
    }
    public function testComplexRoutePathGetMatching(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities/9c5b94b1-35ad-49bb-b118-8e8fc24abf80/actions/action_name';
        $responseFormat = 'text/html';
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);
        $routePathDefinition = 'GET|POST /entities/{entityUUID:uuid4}/actions/{action:regexp([a-z_]+?)}';
        $route = new Route($routePathDefinition, function (string $entityUUID, string $action): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
    }
    public function testComplexRoutePathPostMatching(): void
    {
        $requestMethod = 'POST';
        $requestPath = '/entities/9c5b94b1-35ad-49bb-b118-8e8fc24abf80/actions/longer_action_name';
        $responseFormat = 'text/html';
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);
        $routePathDefinition = 'GET|POST /entities/{entityUUID:uuid4}/actions/{action:regexp([a-z_]+?)}';
        $route = new Route($routePathDefinition, function (string $entityUUID, string $action): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
    }

    public function testPositiveIntegerMatching(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities/0';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = '/entities/{amount:int}';
        $route = new Route($routePathDefinition, function (int $amount): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));

        $requestPath = '/entities/423';
        $routePathDefinition = '/entities/{amount:int}';
        $route = new Route($routePathDefinition, function (int $amount): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));

        $requestPath = '/entities/-5';
        $routePathDefinition = '/entities/{amount:int}';
        $route = new Route($routePathDefinition, function (int $amount): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
    }

    public function testFloatMatching(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities/0';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = '/entities/{amount:float}';
        $route = new Route($routePathDefinition, function (string $amount): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));

        $requestPath = '/entities/1.2';
        $routePathDefinition = '/entities/{amount:float}';
        $route = new Route($routePathDefinition, function (string $amount): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));

        $requestPath = '/entities/-8.53';
        $routePathDefinition = '/entities/{amount:float}';
        $route = new Route($routePathDefinition, function (string $amount): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
    }

    public function testBooleanMatching(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities/true';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = '/entities/{state:bool}';
        $route = new Route($routePathDefinition, function (bool $state): void {});
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));

        $requestPath = '/entities/false';
        $routePathDefinition = '/entities/{state:bool}';
        $route = new Route($routePathDefinition, function (bool $state): void {});
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }

    public function testMethodMismatching(): void
    {
        $requestMethod = 'PUT';
        $requestPath = '/entities';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = '/entities'; // GET is assumed by default
        $route = new Route($routePathDefinition, function (): void {});
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }

    public function testIntegerMisatching(): void
    {
        $requestMethod = 'PUT';
        $requestPath = '/entities/abc';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = '/entities/{entityID:int}';
        $route = new Route($routePathDefinition, function (int $entityID): void {});
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }

    public function testBooleanMisatching(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities/1';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = '/entities/{state:bool}';
        $route = new Route($routePathDefinition, function (bool $state): void {});
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }

    public function testFormatMatching(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = '/entities.html';
        $route = new Route($routePathDefinition, function (bool $state): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));

        $responseFormat = 'text/html';
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = '/entities.htm';
        $route = new Route($routePathDefinition, function (bool $state): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));

        $responseFormat = 'application/json';
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = '/entities.json';
        $route = new Route($routePathDefinition, function (bool $state): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));

    }

    public function testFailedJsonFormatMatching(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities';
        $responseFormat = 'text/html';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse(responseFormat: $responseFormat);

        $routePathDefinition = '/entities.json';
        $route = new Route($routePathDefinition, function (bool $state): void {});
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }

    public function testIntegerPathParameterInHandler(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities/24782';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse();
        $routePathDefinition = '/entities/{entityID:int}';
        $route = new Route($routePathDefinition, function (int $entityID = 0): int {
            return $entityID;
        });
        $result = $route->invoke(request: $request, response: $response);
        $this->assertSame(24782, $result);
    }

    public function testStringPathParameterInHandler(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities/string-id';
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse();
        $routePathDefinition = '/entities/{entityID:string}';
        $route = new Route($routePathDefinition, function (string $entityID = ''): string {
            return $entityID;
        });
        $result = $route->invoke(request: $request, response: $response);
        $this->assertSame('string-id', $result);
    }

    public function testPathParameterOverQueryStringParameterInHandler(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities/123?entityID=345';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse();
        $routePathDefinition = '/entities/{entityID:int}';
        $route = new Route($routePathDefinition, function (int $entityID = 0): int {
            return $entityID;
        });
        $result = $route->invoke(request: $request, response: $response);
        $this->assertSame(123, $result);
    }

    public function testPathParameterTypeCastingInHandler(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/entities/abc';
        $queryString = 'entityID=345';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = '/entities/{entityID:string}';
        $route = new Route($routePathDefinition, function (#[CastInt] int $entityID = 0): int {
            return $entityID;
        });
        $result = $route->invoke(request: $request, response: $response);
        $this->assertSame(0, $result);
    }

    public function testPathMissingDefaultValueParameterTypeInHandler(): void
    {
        $this->expectException(ValidationFailedException::class);
        $requestMethod = 'GET';
        $requestPath = "/entities/123";
        $queryString = 'entityID=345';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new MockResponse();
        $routePathDefinition = '/entities/{entityID:string}';
        $route = new Route($routePathDefinition, function (int $entityID, int $param): int {
            return $entityID;
        });
        $route->invoke(request: $request, response: $response);
    }

    public function testPathIvalidParameterTypeInHandler(): void
    {
        $this->expectException(TypeError::class);
        $requestMethod = 'GET';
        $requestPath = "/entities/abc";
        $queryString = 'entityID=345';

        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: $queryString);
        $response = new Response();
        $routePathDefinition = '/entities/{entityID:string}';
        $route = new Route($routePathDefinition, function (int $entityID = 0): int {
            return $entityID;
        });
        $route->invoke(request: $request, response: $response);
    }

    public function testInvalidFormatMatching(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $routePathDefinition = '/entities.invalid';
        new Route($routePathDefinition, function (bool $state): void {});
    }

    public function testInvalidPathDefinition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $routePathDefinition = '/entities/{entityUUID:uuid4'; // missing closing brace
        new Route($routePathDefinition, function (): void {});
    }

    public function testInvalidPathTypeDefinition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $routePathDefinition = '/entities/{entityUUID:unsupported_type}';
        new Route($routePathDefinition, function (): void {});
    }

    public function testInvalidRegexpDefinition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $routePathDefinition = 'POST /entities/{entityUUID:regexp()}'; // missing regexp pattern
        new Route($routePathDefinition, function (): void {});
    }

    public function testCustomException(): void
    {
        $this->expectException(TestException::class);
        $requestMethod = 'GET';
        $requestPath = '/';
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath, queryString: null);
        $response = new MockResponse();
        $routePathDefinition = '/';
        $route = new Route($routePathDefinition, function (): void {
            throw new TestException();
        });
        $route->invoke(request: $request, response: $response);
    }

    public function testCustomExceptionHandler(): void
    {
        $requestMethod = 'GET';
        $requestPath = '/';
        $request = new MockRequest(requestMethod: $requestMethod, requestPath: $requestPath);
        $response = new MockResponse();
        $routePathDefinition = '/';
        $route = new Route($routePathDefinition, function (): void {
            throw new TestException();
        });
        $route->on(TestException::class, function (): string {
            return 'handled';
        });
        $result = $route->invoke(request: $request, response: $response);
        $this->assertSame('handled', $result);
    }

    public function testStaticGetCreation(): void
    {
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::get('/', function (): void {});
        $this->assertSame('^(GET)\s+\/$', $route->getRequestPathRegExp());
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
        $request = new MockRequest(requestMethod: 'POST', requestPath: '/');
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }
    public function testStaticHeadCreation(): void
    {
        $request = new MockRequest(requestMethod: 'HEAD', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::head('/', function (): void {});
        $this->assertSame('^(HEAD)\s+\/$', $route->getRequestPathRegExp());
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }
    public function testStaticPostCreation(): void
    {
        $request = new MockRequest(requestMethod: 'POST', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::post('/', function (): void {});
        $this->assertSame('^(POST)\s+\/$', $route->getRequestPathRegExp());
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }
    public function testStaticPutCreation(): void
    {
        $request = new MockRequest(requestMethod: 'PUT', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::put('/', function (): void {});
        $this->assertSame('^(PUT)\s+\/$', $route->getRequestPathRegExp());
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }
    public function testStaticDeleteCreation(): void
    {
        $request = new MockRequest(requestMethod: 'DELETE', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::delete('/', function (): void {});
        $this->assertSame('^(DELETE)\s+\/$', $route->getRequestPathRegExp());
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }
    public function testStaticConnectCreation(): void
    {
        $request = new MockRequest(requestMethod: 'CONNECT', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::connect('/', function (): void {});
        $this->assertSame('^(CONNECT)\s+\/$', $route->getRequestPathRegExp());
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }
    public function testStaticOptionsCreation(): void
    {
        $request = new MockRequest(requestMethod: 'OPTIONS', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::options('/', function (): void {});
        $this->assertSame('^(OPTIONS)\s+\/$', $route->getRequestPathRegExp());
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }
    public function testStaticTraceCreation(): void
    {
        $request = new MockRequest(requestMethod: 'TRACE', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::trace('/', function (): void {});
        $this->assertSame('^(TRACE)\s+\/$', $route->getRequestPathRegExp());
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $this->assertSame(false, $route->checkMatch(request: $request, response: $response));
    }
    public function testStaticAnyCreation(): void
    {
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::any('/', function (): void {});
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
        $request = new MockRequest(requestMethod: 'POST', requestPath: '/');
        $this->assertSame(true, $route->checkMatch(request: $request, response: $response));
    }
    // public function testMiddleware(): void
    // {
    //     $request = new MockRequest(requestMethod: 'GET', requestPath: '/entity/123');
    //     $response = new MockResponse(responseFormat: 'text/html');
    //     $route = Route::any('/entity/{entityID:int}', function (int $entityID = 0): int {
    //         return $entityID;
    //     })
    //         ->before(function (Request $request, Response $response): void {

    //         })
    //         ->after(function (Response $response, Request $request): void {

    //         });
    //     
    // }
    public function testStateWithMiddleware(): void
    {
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::any('/', 
        function (): string {
            /**
             * @var Route $this
             */
            return $this->getState('test');
        })
            ->before(function (Request $request, Response $response): void {
                /**
                 * @var Route $this
                 */
                $this->setState('test', 'test value 1');
            })
            ->after(function (Response $response, Request $request): void {
                /**
                 * @var Route $this
                 */
                $this->setState('test', 'test value 2');
            });
        $this->assertSame('test value 1', $route->invoke(request: $request, response: $response));
        $this->assertSame('test value 2', $route->getState('test'));
    }
   public function testRequestMiddlewareAlreadySet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::any('/', function (): void {})
            ->before(function (Request $request, Response $response): void {})
            ->setRequestMiddleware(function (Request $request, Response $response): void {});
    }
   public function testResponseMiddlewareAlreadySet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $response = new MockResponse(responseFormat: 'text/html');
        $route = Route::any('/', function (): void {})
            ->after(function (Request $request, Response $response): void {})
            ->setResponseMiddleware(function (Request $request, Response $response): void {});
    }

}