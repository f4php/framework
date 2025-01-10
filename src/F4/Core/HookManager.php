<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\CoreApiInterface;

class HookManager
{
    public const string AFTER_CORE_CONSTRUCT = 'afterCoreConstruct';
    public const string BEFORE_SETUP_REQUEST_RESPONSE = 'beforeSetupRequestResponse';
    public const string AFTER_SETUP_REQUEST_RESPONSE = 'afterSetupRequestResponse';
    public const string BEFORE_SETUP_ENVIRONMENT = 'beforeSetupEnvironment';
    public const string AFTER_SETUP_ENVIRONMENT = 'afterSetupEnvironment';
    public const string BEFORE_SETUP_EMITTER = 'beforeSetupEmitter';
    public const string AFTER_SETUP_EMITTER = 'afterSetupEmitter';
    public const string BEFORE_REGISTER_MODULES = 'beforeRegisterModules';
    public const string AFTER_REGISTER_MODULES = 'afterRegisterModules';
    public const string BEFORE_PROCESS_REQUEST = 'beforeProcessRequest';
    public const string AFTER_PROCESS_REQUEST = 'afterProcessRequest';
    public const string BEFORE_REQUEST_MIDDLEWARE = 'beforeRequestMiddleware';
    public const string AFTER_REQUEST_MIDDLEWARE = 'afterRequestMiddleware';
    public const string BEFORE_ROUTE_GROUP = 'beforeRouteGroup';
    public const string AFTER_ROUTE_GROUP = 'afterRouteGroup';
    public const string BEFORE_ROUTE = 'beforeRoute';
    public const string AFTER_ROUTE = 'afterRoute';
    public const string BEFORE_ROUTING = 'beforeRouting';
    public const string AFTER_ROUTING = 'afterRouting';
    public const string BEFORE_RESPONSE_MIDDLEWARE = 'beforeResponseMiddleware';
    public const string AFTER_RESPONSE_MIDDLEWARE = 'afterResponseMiddleware';
    public const string BEFORE_EMIT_RESPONSE = 'beforeEmitResponse';
    public const string AFTER_EMIT_RESPONSE = 'afterEmitResponse';

    protected static array $hooks = [];
    protected static array $baseContext = [];

    public static function setBaseContext(array $context): void {
        self::$baseContext = $context;
    }
    public static function getBaseContext(): array {
        return self::$baseContext;
    }
    public static function addHook(string $hookName, callable $callback): void {
        self::$hooks[$hookName] = [...self::$hooks[$hookName] ?? [], $callback];
    }
    public static function getHooks(?string $name=null): array {
        return $name ? (self::$hooks[$name]??[]) : self::$hooks;
    }
    public static function resetHooks(?string $name=null): void {
        if($name) {
            self::$hooks[$name] = [];
        }
        else {
            self::$hooks = [];
        }
    }
    public static function triggerHook(string $hookName, array $context): array {
        $results = [];
        if(!empty(self::$hooks[$hookName])) {
            foreach(self::$hooks[$hookName] as $callback) {
                $results[] = $callback([...self::$baseContext, ...$context]);
            }
        }
        return $results;
    }
}
