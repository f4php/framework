<?php

declare(strict_types=1);

namespace F4\Tests;
use PHPUnit\Framework\TestCase;

use F4\Core;
use F4\Core\CoreApiInterface;
use F4\Core\Request;
use F4\Core\Response;
use F4\Core\Route;
use F4\Core\Router;
use F4\ModuleInterface;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;


final class CoreTest extends TestCase
{

    public function testIncomplete(): void {
        $this->markTestIncomplete(
            'This test has not been implemented yet.',
        );
    }

    // public function testSimpleModulesSetup(): void
    // {
    //     $requestMethod = 'GET';
    //     $queryString = '';
    //     $requestPath = "/test";
    //     $psr17Factory = new Psr17Factory();
    //     $creator = new ServerRequestCreator(
    //         $psr17Factory, // ServerRequestFactory
    //         $psr17Factory, // UriFactory
    //         $psr17Factory, // UploadedFileFactory
    //         $psr17Factory  // StreamFactory
    //     );
    //     parse_str(string: $queryString, result: $queryParams);
    //     $request = Request::fromPsr(psrRequest: $creator->fromArrays([
    //         'REQUEST_METHOD' => $requestMethod,
    //         'REQUEST_URI' => $requestPath,
    //         'QUERY_STRING' => $queryString,
    //     ], [], [], $queryParams, null, [], null));
    //     $response = Response::fromPsr(psrResponse: $psr17Factory->createResponse());

    //     $f4Core = new Core();
    //     $f4 = $f4Core->getCoreApiProxy();

    //     $module1 = new class ($f4) implements ModuleInterface {
    //         public function __construct(CoreApiInterface &$f4)
    //         {
    //             $f4->addRoute('GET /test', function () {
    //                 return 'module-1';
    //             });
    //         }
    //     };
    //     $module2 = new class ($f4) implements ModuleInterface {
    //         public function __construct(CoreApiInterface &$f4)
    //         {
    //             $f4->addRoute('GET /test', function () {
    //                 return 'module-2';
    //             });
    //         }
    //     };
    //     $f4Core
    //         ->setUpRequestResponse(function($callback) use ($request, $response): void {
    //             $callback($request, $response);
    //         })
    //         ->setUpEnvironment(function($callback): void {
    //             $callback(true, true);
    //         })
    //         ->registerModules(customHandler: function ($callback) use ($module1, $module2): mixed {
    //             return $callback([
    //                 'test-module-1' => $module1,
    //                 'test-module-2' => $module2,
    //             ]);
    //         })
    //         ->processRequest()
    //         // There's no need to render anything
    //         // ->renderResponse(function(){}) 
    //         ->restoreEnvironment(function($callback): void {
    //             $callback(true, true);
    //         });
            
    //     $this->assertSame('module-1', $f4->getResponse()->getPartialResults()[0]);
    //     $this->assertSame('module-2', $f4->getResponse()->getPartialResults()[1]);
    // }
    // public function testComplexModulesBeforeAfter(): void
    // {
    //     $requestMethod = 'GET';
    //     $queryString = '';
    //     $requestPath = "/test";
    //     $psr17Factory = new Psr17Factory();
    //     $creator = new ServerRequestCreator(
    //         $psr17Factory, // ServerRequestFactory
    //         $psr17Factory, // UriFactory
    //         $psr17Factory, // UploadedFileFactory
    //         $psr17Factory  // StreamFactory
    //     );
    //     parse_str(string: $queryString, result: $queryParams);
    //     $request = Request::fromPsr(psrRequest: $creator->fromArrays([
    //         'REQUEST_METHOD' => $requestMethod,
    //         'REQUEST_URI' => $requestPath,
    //         'QUERY_STRING' => $queryString,
    //     ], [], [], $queryParams, null, [], null));
    //     $response = Response::fromPsr(psrResponse: $psr17Factory->createResponse());

    //     $f4Core = new Core();
    //     $f4 = $f4Core->getCoreApiProxy();

    //     $module1 = new class ($f4) implements ModuleInterface {
    //         public function __construct(CoreApiInterface &$f4)
    //         {
    //             $f4->setRequestHandler(function ($request) use ($f4): void {
    //                 $previousValue = $request->getHeaderLine('X-Test-Request-Header');
    //                 $f4->setRequest($request->withHeader('X-Test-Request-Header', "{$previousValue}[test-global]"));
    //             });
    //             $f4->setResponseHandler(function ($response) use ($f4): void {
    //                 $previousValue = $response->getHeaderLine('X-Test-Response-Header');
    //                 $f4->setResponse($response->withHeader('X-Test-Response-Header', "{$previousValue}[test-global]"));
    //             });
    //             $f4->addRoute('GET /test', function () {
    //                 return 'module-1';
    //             })
    //                 ->before(function ($request) use ($f4): void {
    //                     $previousValue = $request->getHeaderLine('X-Test-Request-Header');
    //                     $f4->setRequest($request->withHeader('X-Test-Request-Header', "{$previousValue}[test-module1-route-before]"));
    //                 })
    //                 ->after(function ($response) use ($f4): void {
    //                     $previousValue = $response->getHeaderLine('X-Test-Response-Header');
    //                     $f4->setResponse($response->withHeader('X-Test-Response-Header', "{$previousValue}[test-module1-route-after]"));
    //                 });
    //         }
    //     };
    //     $module2 = new class ($f4) implements ModuleInterface {
    //         public function __construct(CoreApiInterface &$f4)
    //         {
    //             $f4->addModuleRequestHandler($this, function ($request) use ($f4): void {
    //                 $previousValue = $request->getHeaderLine('X-Test-Request-Header');
    //                 $f4->setRequest($request->withHeader('X-Test-Request-Header', "{$previousValue}[test-module2]"));
    //             });
    //             $f4->addModuleResponseHandler($this, function ($response) use ($f4): void {
    //                 $previousValue = $response->getHeaderLine('X-Test-Response-Header');
    //                 $f4->setResponse($response->withHeader('X-Test-Response-Header', "{$previousValue}[test-module2]"));
    //             });
    //             $f4->addRoute('GET /test', function () {
    //                 return 'module-2';
    //             })
    //                 ->before(function ($request) use ($f4): void {
    //                     $previousValue = $request->getHeaderLine('X-Test-Request-Header');
    //                     $f4->setRequest($request->withHeader('X-Test-Request-Header', "{$previousValue}[test-module2-route-before]"));
    //                 })
    //                 ->after(function ($response) use ($f4): void {
    //                     $previousValue = $response->getHeaderLine('X-Test-Response-Header');
    //                     $f4->setResponse($response->withHeader('X-Test-Response-Header', "{$previousValue}[test-module2-route-after]"));
    //                 });
    //         }
    //     };
    //     $f4Core
    //         ->setUpRequestResponse(function($callback) use ($request, $response): void {
    //             $callback($request, $response);
    //         })
    //         ->setUpEnvironment(function($callback): void {
    //             $callback(true, true);
    //         })
    //         ->registerModules(customHandler: function ($callback) use ($module1, $module2): mixed {
    //             return $callback([
    //                 'test-module-1' => $module1,
    //                 'test-module-2' => $module2,
    //             ]);
    //         })
    //         ->processRequest()
    //         // There's no need to render anything
    //         // ->renderResponse(function(){}) 
    //         ->restoreEnvironment(function($callback): void {
    //             $callback(true, true);
    //         });

    //     $this->assertSame('[test-global][test-module1-route-before][test-module2][test-module2-route-before]', $f4->getRequest()->getHeaderLine('X-Test-Request-Header'));
    //     $this->assertSame('[test-module1-route-after][test-module2-route-after][test-module2][test-global]', $f4->getResponse()->getHeaderLine('X-Test-Response-Header'));
    //     $this->assertSame('module-1', $f4->getResponse()->getPartialResults()[0]);
    //     $this->assertSame('module-2', $f4->getResponse()->getPartialResults()[1]);
    // }

    // public function testModulesWithHandledExceptions(): void
    // {
    //     $requestMethod = 'GET';
    //     $queryString = '';
    //     $requestPath = "/test";
    //     $psr17Factory = new Psr17Factory();
    //     $creator = new ServerRequestCreator(
    //         $psr17Factory, // ServerRequestFactory
    //         $psr17Factory, // UriFactory
    //         $psr17Factory, // UploadedFileFactory
    //         $psr17Factory  // StreamFactory
    //     );
    //     parse_str(string: $queryString, result: $queryParams);
    //     $request = Request::fromPsr(psrRequest: $creator->fromArrays([
    //         'REQUEST_METHOD' => $requestMethod,
    //         'REQUEST_URI' => $requestPath,
    //         'QUERY_STRING' => $queryString,
    //     ], [], [], $queryParams, null, [], null));
    //     $response = Response::fromPsr(psrResponse: $psr17Factory->createResponse());

    //     $f4Core = new Core();
    //     $f4 = $f4Core->getCoreApiProxy();

    //     $module1 = new class ($f4) implements ModuleInterface {
    //         public function __construct(CoreApiInterface &$f4)
    //         {
    //             $f4->addRoute('GET /test', function () {
    //                 throw new TestException();
    //             })
    //                 ->on(TestException::class, function() {
    //                     return 'handled-in-module-1';
    //                 });
    //         }
    //     };
    //     $module2 = new class ($f4) implements ModuleInterface {
    //         public function __construct(CoreApiInterface &$f4)
    //         {
    //             $f4->addRoute('GET /test', function () {
    //                 throw new TestException();
    //             })
    //                 ->on(TestException::class, function() {
    //                     return 'handled-in-module-2';
    //                 });
    //         }
    //     };
    //     $f4Core
    //         ->setUpRequestResponse(function($callback) use ($request, $response): void {
    //             $callback($request, $response);
    //         })
    //         ->setUpEnvironment(function($callback): void {
    //             $callback(true, true);
    //         })
    //         ->registerModules(customHandler: function ($callback) use ($module1, $module2): mixed {
    //             return $callback([
    //                 'test-module-1' => $module1,
    //                 'test-module-2' => $module2,
    //             ]);
    //         })
    //         ->processRequest()
    //         // There's no need to render anything
    //         // ->renderResponse(function(){}) 
    //         ->restoreEnvironment(function($callback): void {
    //             $callback(true, true);
    //         });
            
    //     $this->assertSame('handled-in-module-1', $f4->getResponse()->getPartialResults()[0][0]);
    //     $this->assertSame('handled-in-module-2', $f4->getResponse()->getPartialResults()[1][0]);
    // }
    // public function testUnhandledModuleException(): void
    // {
    //     $this->expectException(TestException::class);
    //     $requestMethod = 'GET';
    //     $queryString = '';
    //     $requestPath = "/test";
    //     $psr17Factory = new Psr17Factory();
    //     $creator = new ServerRequestCreator(
    //         $psr17Factory, // ServerRequestFactory
    //         $psr17Factory, // UriFactory
    //         $psr17Factory, // UploadedFileFactory
    //         $psr17Factory  // StreamFactory
    //     );
    //     parse_str(string: $queryString, result: $queryParams);
    //     $request = Request::fromPsr(psrRequest: $creator->fromArrays([
    //         'REQUEST_METHOD' => $requestMethod,
    //         'REQUEST_URI' => $requestPath,
    //         'QUERY_STRING' => $queryString,
    //     ], [], [], $queryParams, null, [], null));
    //     $response = Response::fromPsr(psrResponse: $psr17Factory->createResponse());

    //     $f4Core = new Core();
    //     $f4 = $f4Core->getCoreApiProxy();

    //     $module1 = new class ($f4) implements ModuleInterface {
    //         public function __construct(CoreApiInterface &$f4)
    //         {
    //             $f4->addRoute('GET /test', function () {
    //                 throw new TestException();
    //             });
    //         }
    //     };
    //     $f4Core
    //         ->setUpRequestResponse(function($callback) use ($request, $response): void {
    //             $callback($request, $response);
    //         })
    //         ->setUpEnvironment(function($callback): void {
    //             $callback(true, true);
    //         })
    //         ->registerModules(customHandler: function ($callback) use ($module1): mixed {
    //             return $callback([
    //                 'test-module-1' => $module1,
    //             ]);
    //         })
    //         ->processRequest()
    //         // There's no need to render anything
    //         // ->renderResponse(function(){}) 
    //         ->restoreEnvironment(function($callback): void {
    //             $callback(true, true);
    //         });
    // }


}