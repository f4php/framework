<?php
/**
 *      (c) Copyright 2016-2024 Dennis Kreminsky, dennis at kreminsky dot com
 *
 *      @package F4
 *      @author Dennis Kreminsky, dennis at kreminsky dot com
 *      @copyright Copyright (c) 2012-2024 Dennis Kreminsky, dennis at kreminsky dot com
 */

declare(strict_types=1);

namespace F4;

use Closure;
use ErrorException;
use InvalidArgumentException;
use Throwable;

use F4\Config;
use F4\ModuleInterface;

use F4\Core\CanExtractFormatFromExtensionTrait;

use F4\Core\CoreApiInterface;
use F4\Core\DebuggerInterface;
use F4\Core\ExceptionRenderer;
use F4\Core\HookManager;
use F4\Core\LocalizerInterface;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;

use F4\Core\ResponseEmitter\ResponseEmitterInterface;

use F4\Core\Request;
use F4\Core\Response;
use F4\Core\Route;
use F4\Core\RouteGroup;
use F4\Core\RouterInterface;

use function class_exists;
use function date_default_timezone_set;
use function error_reporting;
use function get_class;
use function is_a;
use function is_object;
use function mb_internal_encoding;
use function mb_regex_encoding;
use function ob_end_flush;
use function ob_start;
use function php_sapi_name;
use function restore_error_handler;
use function restore_exception_handler;
use function session_name;
use function session_set_cookie_params;
use function session_start;
use function set_error_handler;
use function set_exception_handler;

class Core implements CoreApiInterface
{
    use CanExtractFormatFromExtensionTrait;

    protected array $modules = [];
    protected CoreApiInterface $coreApiProxy;
    protected DebuggerInterface $debugger;
    protected ResponseEmitterInterface $emitter;
    protected RouterInterface $router;
    protected LocalizerInterface $localizer;
    protected RequestInterface $request;
    protected ResponseInterface $response;

    // TODO: add localizer
    public function __construct(string $coreApiProxyClassName = Config::CORE_API_PROXY_CLASS, string $routerClassName = Config::CORE_ROUTER_CLASS, string $debuggerClassName = Config::CORE_DEBUGGER_CLASS)
    {
        if (Config::DEBUG_MODE) {
            $debugger = new $debuggerClassName();
            $this->setDebugger(debugger: $debugger);
        }
        $coreApiProxy = new $coreApiProxyClassName($this);
        $this->setCoreApiProxy(coreApiProxy: $coreApiProxy);
        HookManager::setBaseContext(['f4'=>$coreApiProxy]);
        $router = new $routerClassName();
        $this->setRouter(router: $router);
        HookManager::triggerHook(hookName: HookManager::AFTER_CORE_CONSTRUCT, context: []);
    }
    public function setUpRequestResponse(?callable $customHandler = null): static
    {
        HookManager::triggerHook(hookName: HookManager::BEFORE_SETUP_REQUEST_RESPONSE, context: []);
        match ($customHandler) {
            null => $this->setUpRequestResponseNormally(),
            default => Closure::fromCallable(callback: $customHandler)->call($this, $this->setUpRequestResponseNormally(...))
        };
        HookManager::triggerHook(hookName: HookManager::AFTER_SETUP_REQUEST_RESPONSE, context: []);
        return $this;
    }
    public function setUpEnvironment(?callable $customHandler = null): static
    {
        HookManager::triggerHook(hookName: HookManager::BEFORE_SETUP_ENVIRONMENT, context: []);
        match ($customHandler) {
            null => $this->setUpEnvironmentNormally(),
            default => Closure::fromCallable(callback: $customHandler)->call($this, $this->setUpEnvironmentNormally(...))
        };
        HookManager::triggerHook(hookName: HookManager::AFTER_SETUP_ENVIRONMENT, context: []);
        return $this;
    }
    public function setUpEmitter(?callable $customHandler = null): static
    {
        HookManager::triggerHook(hookName: HookManager::BEFORE_SETUP_EMITTER, context: []);
        match ($customHandler) {
            null => $this->setUpEmitterNormally(),
            default => Closure::fromCallable(callback: $customHandler)->call($this, $this->setUpEmitterNormally(...))
        };
        HookManager::triggerHook(hookName: HookManager::AFTER_SETUP_EMITTER, context: ['emitter'=>$this->emitter]);
        return $this;
    }
    public function registerModules(?callable $customHandler = null): static
    {
        HookManager::triggerHook(hookName: HookManager::BEFORE_REGISTER_MODULES, context: ['modules'=>$this->modules]);
        match ($customHandler) {
            null => $this->registerModulesNormally(),
            default => Closure::fromCallable(callback: $customHandler)->call($this, $this->registerModulesNormally(...))
        };
        HookManager::triggerHook(hookName: HookManager::AFTER_REGISTER_MODULES, context: ['modules'=>$this->modules]);
        return $this;
    }
    public function processRequest(?callable $customHandler = null): static
    {
        HookManager::triggerHook(hookName: HookManager::BEFORE_PROCESS_REQUEST, context: ['request'=>$this->request]);
        match ($customHandler) {
            null => $this->processRequestNormally(),
            default => Closure::fromCallable(callback: $customHandler)->call($this, $this->processRequestNormally(...))
        };
        HookManager::triggerHook(hookName: HookManager::AFTER_PROCESS_REQUEST, context: ['request'=>$this->request]);
        return $this;
    }
    public function emitResponse(?callable $customHandler = null): static
    {
        HookManager::triggerHook(hookName: HookManager::BEFORE_EMIT_RESPONSE, context: ['response'=>$this->response]);
        if(Config::DEBUG_MODE && $this->debugger->checkIfEnabledByRequest($this->request)) {
            $this->debugger->captureAndEmit(emitCallback: function() use ($customHandler): void {
                match ($customHandler) {
                    null => $this->emitResponseNormally(),
                    default => Closure::fromCallable(callback: $customHandler)->call($this, $this->emitResponseNormally(...))
                };
                HookManager::triggerHook(hookName: HookManager::AFTER_EMIT_RESPONSE, context: ['response'=>$this->response]);
            });
        }
        else {
            match ($customHandler) {
                null => $this->emitResponseNormally(),
                default => Closure::fromCallable(callback: $customHandler)->call($this, $this->emitResponseNormally(...))
            };
            HookManager::triggerHook(hookName: HookManager::AFTER_EMIT_RESPONSE, context: ['response'=>$this->response]);
        }
        return $this;
    }
    public function restoreEnvironment(?callable $customHandler = null): static
    {
        match ($customHandler) {
            null => $this->restoreEnvironmentNormally(),
            default => Closure::fromCallable(callback: $customHandler)->call($this, $this->restoreEnvironmentNormally(...))
        };
        return $this;
    }
    protected function setUpRequestResponseNormally(?RequestInterface $request = null, ?ResponseInterface $response = null): void
    {
        $this->setRequest(request: $request ?? new Request());
        $this->setResponse(response: $response ?? new Response());
    }
    protected function setUpEnvironmentNormally(bool $skipOutputBuffering = false, bool $skipErrorHandling = false): void
    {
        if(!$skipOutputBuffering) {
            $this->enableOutputBufferCapture();
        }
        if(!$skipErrorHandling) {
            set_exception_handler(callback: function (Throwable $exception): void {
                $format = $this->getResponse() ? $this->getResponseFormat() : Config::DEFAULT_RESPONSE_FORMAT;
                ExceptionRenderer::handleException(exception: $exception, format: $format);
            });
            set_error_handler(callback: function ($errno, $errstr, $errfile, $errline): bool {
                if (error_reporting() === 0) {
                    return true; // originating statement used "@" to disable error checking
                }
                throw new ErrorException(message: $errstr, code: 500, severity: $errno, filename: $errfile, line: $errline);
            }, error_levels: E_ALL);
        }
        if (false === mb_internal_encoding(encoding: Config::RESPONSE_CHARSET)) {
            throw new ErrorException(message: 'Failed to set internal character encoding');
        }
        if (false === mb_regex_encoding(encoding: Config::RESPONSE_CHARSET)) {
            throw new ErrorException(message: 'Failed to set regex character encoding');
        }
        if (!empty(Config::TIMEZONE)) {
            $this->setTimezone(timezone: Config::TIMEZONE);
        }
        if(Config::SESSION_ENABLED) {
            session_name(Config::SESSION_COOKIE_NAME);
            session_set_cookie_params([
                'lifetime'  => Config::SESSION_LIFETIME,
                'path'      => Config::SESSION_PATH,
                'domain'    => Config::SESSION_DOMAIN,
                'secure'    => Config::SESSION_SECURE_ONLY,
                'httponly'  => Config::SESSION_HTTP_ONLY,
                'samesite'  => Config::SESSION_SAME_SITE
            ]);
            if(!session_start()) {
                throw new ErrorException('Failed to initialize session');
            }
        }
    }
    protected function setupEmitterNormally(): static
    {
        $this->setResponseFormat(format: $format = match ($extension = $this->getRequest()->getExtension()) {
            null => Config::DEFAULT_RESPONSE_FORMAT,
            default => $this->getResponseFormatFromExtension(extension: $extension)
        });
        $this->emitter = match (empty(Config::RESPONSE_EMITTERS[$format]['class']) || !class_exists(class: Config::RESPONSE_EMITTERS[$format]['class'], autoload: true)) {
            true => throw new ErrorException(message: "Failed to locate emitter for '{$format}'"),
            default => new (Config::RESPONSE_EMITTERS[$format]['class'])($this)
        };
        return $this;
    }
    protected function registerModulesNormally(array $modules = Config::MODULES): static 
    {
        foreach ($modules as $name => $module) {
            if (!is_a(object_or_class: $module, class: ModuleInterface::class, allow_string: true)) {
                throw new InvalidArgumentException(message: 'Modules must implement ModuleInterface');
            }
            $module = match (is_object(value: $module)) {
                false => new $module($this->coreApiProxy),
                default => $module
            };
            $name = $name ?: get_class(object: $module);
            $this->modules[$name] = $module;
        }
        return $this;
    }
    protected function processRequestNormally(): void
    {
        $this->response->setData($this->router->invokeMatchingRoutes(request: $this->request, response: $this->response));
    }
    protected function emitResponseNormally(): void
    {
        if (php_sapi_name() === 'cli') {
            $this->setResponseFormat(format: Core\ResponseEmitter\Cli::INTERNAL_MIME_TYPE);
        }
        if (empty(Config::RESPONSE_EMITTERS[$this->getResponseFormat()])) {
            throw new ErrorException(message: "Failed to find renderer for '{$this->getResponseFormat()}'");
        }
        if(!$this->emit(response: $this->getResponse(), request: $this->getRequest())) {
            throw new ErrorException(message: 'Failed to emit response');
        }
    }
    protected function restoreEnvironmentNormally(bool $skipOutputBuffering = false, bool $skipErrorHandling = false): void
    {
        if(!$skipOutputBuffering) {
            $this->flushOutputBufferCapture();
        }
        if(!$skipErrorHandling) {
            restore_error_handler();
            restore_exception_handler();
        }
    }
    protected function enableOutputBufferCapture(): void
    {
        if (false === ob_start()) {
            throw new ErrorException(message: 'Failed to enable output buffer cache');
        }
    }
    protected function flushOutputBufferCapture(): void
    {
        if (false === ob_end_flush()) {
            throw new ErrorException(message: 'Failed to flush output buffer cache');
        }
    }

    // All Core API methods are to be implemented below
    public function setRequestHandler(callable $handler): static 
    {
        $this->router->setRequestMiddleware(requestMiddleware: $handler);
        return $this;
    }
    public function before(callable $handler): static 
    {
        return $this->setRequestHandler(handler: $handler);
    }
    public function setResponseHandler(callable $handler): static 
    {
        $this->router->setResponseMiddleware(responseMiddleware: $handler);
        return $this;
    }
    public function after(callable $handler): static 
    {
        return $this->setResponseHandler(handler: $handler);
    }
    public function addExceptionHandler(string $exceptionClassName, callable $handler): static 
    {
        $this->router->addExceptionHandler(exceptionClassName: $exceptionClassName, exceptionHandler: $handler);
        return $this;
    }
    public function on(string $exceptionClassName, callable $handler): static 
    {
        return $this->addExceptionHandler(exceptionClassName: $exceptionClassName,handler: $handler);
    }
    public function setRequest(RequestInterface $request): static
    {
        $this->request = $request;
        return $this;
    }
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
    public function setResponse(ResponseInterface $response): static
    {
        $this->response = $response;
        return $this;
    }
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
    public function addRoute(Route|string $routeOrPath, ?callable $handler = null): Route
    {
        return $this->router->addRoute(routeOrPath: $routeOrPath, handler: $handler);
    }
    public function addRouteGroup(Routegroup $routeGroup): RouteGroup
    {
        return $this->router->addRouteGroup(routeGroup: $routeGroup);
    }
    public function addHook(string $hookName, callable $callback): static
    {
        HookManager::addHook(hookName: $hookName, callback: $callback);
        return $this;
    }
    public function setResponseFormat(string $format): static
    {
        $this->getResponse()->setResponseFormat($format);
        return $this;
    }
    public function getResponseFormat(): string
    {
        return $this->getResponse()->getResponseFormat();
    }
    public function getTemplate(?string $format = null): string
    {
        return $this->getResponse()->getTemplate(format: $format);
    }
    public function getCoreApiProxy(): CoreApiInterface
    {
        return $this->coreApiProxy;
    }
    public function setCoreApiProxy(CoreApiInterface $coreApiProxy): static
    {
        $this->coreApiProxy = $coreApiProxy;
        return $this;
    }
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }
    public function setRouter(RouterInterface $router): static
    {
        $this->router = $router;
        return $this;
    }
    public function getDebugger(): DebuggerInterface
    {
        return $this->debugger;
    }
    public function setDebugger(DebuggerInterface $debugger): static
    {
        $this->debugger= $debugger;
        return $this;
    }
    public function setTemplate(string $template, ?string $format = null): static
    {
        $this->getResponse()->setTemplate(template: $template, format: $format);
        return $this;
    }
    public function setTimezone(string $timezone): static
    {
        date_default_timezone_set(timezoneId: $timezone);
        return $this;
    }
    public function emit(?ResponseInterface $response = null, ?RequestInterface $request = null): bool
    {
        return $this->emitter->emit(response: $response, request: $request);
    }
}
