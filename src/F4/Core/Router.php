<?php

declare(strict_types=1);

namespace F4\Core;

use ErrorException;
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
    protected function getMatchingRouteGroups(RequestInterface $request, ResponseInterface $response): array
    {
        $matchingGroups = \array_filter($this->routeGroups, function (RouteGroup $routeGroup) use ($request, $response) {
            return $routeGroup->getMatchingRoutes($request, $response);
        });
        return $matchingGroups;
    }
    public function invokeMatchingRoutes(RequestInterface &$request, ResponseInterface &$response): mixed
    {
        $result = null;
        $matchingRouteGroups = $this->getMatchingRouteGroups(request: $request, response: $response);
        if(\count($matchingRouteGroups) > 1) {
            throw new ErrorException(message: 'Matching multiple route groups per request is not allowed');
        }
        $matchingRoutes = \array_reduce(array: $matchingRouteGroups, callback: function($result, $routeGroup) use ($request, $response): array {
            return [...$result, ...$routeGroup->getMatchingRoutes($request, $response)];
        }, initial: []);
        if(\count($matchingRoutes) > 1) {
            throw new ErrorException(message: 'Matching multiple routes per request is not allowed');
        }
        try {
            if(isset($this->requestMiddleware)) {
                $this->invokeRequestMiddleware(request: $request, response: $response);
            }
            if ($matchingRoutes) {
                foreach($matchingRouteGroups as $routeGroup) {
                    if($routeGroup->hasMatchingRoutes($request, $response)) {
                        $result = $routeGroup->invoke($request, $response)[0] ?? null; // we assume there's at most one matching group per request
                    }
                }
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
