<?php

namespace F4\Tests;

use F4\Core\CoreApiInterface;
use F4\Core\Route;
use F4\Core\RouteGroup;

use F4\ModuleInterface;

class MockModule implements ModuleInterface
{
    public function __construct(CoreApiInterface &$f4)
    {
        $f4 
            ->before(function ($request, $response) {
            })
            ->after(function ($response, $request, $route) {
            })
            ;

        $f4->addRouteGroup(RouteGroup::fromRoutes(
                Route::any('/test', function(): bool {
                    return true;
                })
                ->setTemplate('test-template.pug', 'text/html')
                ->before(function ($request, $response) {
                })
                ->after(function ($response, $request, $route) {
                })
            )
            ->before(function ($request, $response) {
            })
            ->after(function ($response, $request, $route) {
            })
        );

    }

}