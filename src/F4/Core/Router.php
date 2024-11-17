<?php

declare(strict_types=1);

namespace F4\Core;

use InvalidArgumentException;
use Throwable;

use Composer\Pcre\Preg;

use F4\Core\ExceptionHandlerTrait;
use F4\Core\MiddlewareAwareTrait;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\Route;

use F4\Core\RouterInterface;

class Router implements RouterInterface
{
    use ExceptionHandlerTrait;
    use MiddlewareAwareTrait;

    protected array $requestHandlers = [];
    protected array $responseHandlers = [];
    protected array $exceptionHandlers = [];
    protected array $routeGroups = [];

    public function __construct() {
        /**
         * This is the default group, all ungrouped routes end up here
         */
        $this->routeGroups[0] = new RouteGroup();
    }
    public function addRouteGroup(RouteGroup $routeGroup): RouteGroup {
        return $this->routeGroups[] = $routeGroup;
    }
    public function addRoute(Route|string $routeOrPath, ?callable $handler = null): Route
    {
        return $this->routeGroups[0]->addRoute(match ($routeOrPath instanceof Route) {
            true => $routeOrPath,
            default => new Route(pathDefinition: $routeOrPath, handler: $handler)
        });
    }
    public function getRouteGroups(): array
    {
        \usort(array: $this->routeGroups, callback: function (RouteGroup $groupA, RouteGroup $groupB): int {
            return (int) $groupB->getPriority() - (int) $groupA->getPriority();
        });
        return $this->routeGroups;

    }
    public function invokeMatchingRoutes(RequestInterface &$request, ResponseInterface &$response): mixed
    {
        $result = [];
        try {
            if(isset($this->requestMiddleware)) {
                $this->invokeRequestMiddleware(request: $request, response: $response);
            }
            foreach($this->getRouteGroups() as $routeGroup) {
                $result = [...$result, ...$routeGroup->invoke($request, $response)];
            }
            if(isset($this->responseMiddleware)) {
                $this->invokeResponseMiddleware(response: $response, request: $request);
            }
        }
        catch (Throwable $exception) {
            $handled = false;
            foreach ($this->exceptionHandlers as $className => $handler) {
                if (!$className || ($exception instanceof $className)) {
                    $result[] = $handler->call($this, $exception, $request, $response);
                    $handled = true;
                }
            }
            if(!$handled) {
                throw $exception;
            }
        }
        return $result;
    }
}
