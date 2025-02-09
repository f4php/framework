<?php

declare(strict_types=1);

namespace F4;
class HookManager
{
    public const string AFTER_CORE_CONSTRUCT = 'AFTER_CORE_CONSTRUCT';
    public const string BEFORE_SETUP_REQUEST_RESPONSE = 'BEFORE_SETUP_REQUEST_RESPONSE';
    public const string AFTER_SETUP_REQUEST_RESPONSE = 'AFTER_SETUP_REQUEST_RESPONSE';
    public const string BEFORE_SETUP_ENVIRONMENT = 'BEFORE_SETUP_ENVIRONMENT';
    public const string AFTER_SETUP_ENVIRONMENT = 'AFTER_SETUP_ENVIRONMENT';
    public const string BEFORE_SETUP_EMITTER = 'BEFORE_SETUP_EMITTER';
    public const string AFTER_SETUP_EMITTER = 'AFTER_SETUP_EMITTER';
    public const string BEFORE_REGISTER_MODULES = 'BEFORE_REGISTER_MODULES';
    public const string AFTER_REGISTER_MODULES = 'AFTER_REGISTER_MODULES';
    public const string BEFORE_PROCESS_REQUEST = 'BEFORE_PROCESS_REQUEST';
    public const string BEFORE_REQUEST_MIDDLEWARE = 'BEFORE_REQUEST_MIDDLEWARE';
    public const string AFTER_REQUEST_MIDDLEWARE = 'AFTER_REQUEST_MIDDLEWARE';
    public const string BEFORE_ROUTING = 'BEFORE_ROUTING';
    public const string BEFORE_ROUTE_GROUP_REQUEST_MIDDLEWARE = 'BEFORE_ROUTE_GROUP_REQUEST_MIDDLEWARE';
    public const string AFTER_ROUTE_GROUP_REQUEST_MIDDLEWARE = 'AFTER_ROUTE_GROUP_REQUEST_MIDDLEWARE';
    public const string BEFORE_ROUTE_GROUP = 'BEFORE_ROUTE_GROUP';
    public const string BEFORE_ROUTE_REQUEST_MIDDLEWARE = 'BEFORE_ROUTE_REQUEST_MIDDLEWARE';
    public const string AFTER_ROUTE_REQUEST_MIDDLEWARE = 'AFTER_ROUTE_REQUEST_MIDDLEWARE';
    public const string BEFORE_ROUTE = 'BEFORE_ROUTE';
    public const string AFTER_ROUTE = 'AFTER_ROUTE';
    public const string BEFORE_ROUTE_RESPONSE_MIDDLEWARE = 'BEFORE_ROUTE_RESPONSE_MIDDLEWARE';
    public const string AFTER_ROUTE_RESPONSE_MIDDLEWARE = 'AFTER_ROUTE_RESPONSE_MIDDLEWARE';
    public const string AFTER_ROUTE_GROUP = 'AFTER_ROUTE_GROUP';
    public const string BEFORE_ROUTE_GROUP_RESPONSE_MIDDLEWARE = 'BEFORE_ROUTE_GROUP_RESPONSE_MIDDLEWARE';
    public const string AFTER_ROUTE_GROUP_RESPONSE_MIDDLEWARE = 'AFTER_ROUTE_GROUP_RESPONSE_MIDDLEWARE';
    public const string AFTER_ROUTING = 'AFTER_ROUTING';
    public const string BEFORE_RESPONSE_MIDDLEWARE = 'BEFORE_RESPONSE_MIDDLEWARE';
    public const string AFTER_RESPONSE_MIDDLEWARE = 'AFTER_RESPONSE_MIDDLEWARE';
    public const string AFTER_PROCESS_REQUEST = 'AFTER_PROCESS_REQUEST';
    public const string BEFORE_EMIT_RESPONSE = 'BEFORE_EMIT_RESPONSE';
    public const string AFTER_EMIT_RESPONSE = 'AFTER_EMIT_RESPONSE';
    public const string AFTER_TEMPLATE_CONTEXT_READY = 'AFTER_TEMPLATE_CONTEXT_READY';
    public const string BEFORE_SQL_SUBMIT = 'BEFORE_SQL_SUBMIT';
    public const string AFTER_SQL_SUBMIT = 'AFTER_SQL_SUBMIT';

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
