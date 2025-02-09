<?php

declare(strict_types=1);

namespace F4;

use F4\Config\SensitiveParameter;
use F4\Config\SensitiveParameterKey;

use F4\DB\Adapter\PostgresqlAdapter;

use F4\Core\Localizer;
use F4\Core\Router;
use F4\Core\CoreApiProxy;

abstract class AbstractConfig
{
    public const bool DEBUG_MODE = false;
    public const bool DEBUG_DB_QUERIES = true;
    public const string CORE_DEBUGGER_CLASS = Core\Debugger::class;
    public const string DEBUG_EXTENSION = '+debug';
    public const bool VALIDATOR_ATTRIBUTES_MUST_BE_CLASSES = true;
    public const string DB_HOST = 'localhost';
    public const string DB_CHARSET = 'UTF8';
    public const string DB_PORT = '5432';
    public const string DB_NAME = '';
    public const string DB_USERNAME = '';
    #[SensitiveParameter]
    public const string DB_PASSWORD = '';
    public const string DB_SCHEMA = '';
    public const ?string DB_APP_NAME = null;
    // public const bool DB_ALLOW_UNSAFE_RAW_QUERIES = false;
    public const string DB_ADAPTER_CLASS = PostgresqlAdapter::class;

    public const bool DB_PERSIST = true;
    // public const bool DB_KEEP_NULLS = true;

    public const array MODULES = [];

    public const bool TEMPLATE_CACHE_ENABLED = true;
    public const string TEMPLATE_CACHE_PATH = '/tmp';
    public const int TEMPLATE_CACHE_LIFETIME = 3600;
    public const bool TEMPLATE_RELATIVE_PATHS = true; // search current path in a template for includes and mixins, only applies to Phug renderer
    public const array TEMPLATE_PATHS = [
        __DIR__ . '/../../../templates'
    ];

    public const string RESPONSE_CHARSET = 'utf-8';
    //public const string FILESYSTEM_CHARSET = 'utf-8';

    /**
     * Response format defines which emitter will be used for emitting response
     * Emitter class for default type MUST be defined in RESPONSE_EMITTERS
     */
    public const string DEFAULT_RESPONSE_FORMAT = 'text/html';

    /**
     * 
     * STRICT_RESPONSE_FORMAT_MATCHING === false means relaxed format matching:
     * 
     * new Route("GET /endpoint", ...)      // will match any format, i.e. text/html or application/json
     * new Route("GET /endpoint.html", ...) // will only match text/html format
     * new Route("GET /endpoint.json", ...) // will only match application/json format
     * 
     * STRICT_RESPONSE_FORMAT_MATCHING === true means strict format matching:
     * 
     * new Route("GET /endpoint", ...)      // will only match default format (i.e. text/html) and won't match any other format
     * new Route("GET /endpoint.html", ...) // will only match text/html format
     * new Route("GET /endpoint.json", ...) // will only match application/json format
     * 
     */
    public const bool STRICT_RESPONSE_FORMAT_MATCHING = false;

    public const array RESPONSE_EMITTERS = [
        'text/html' => [
            'extensions' => ['.html', '.htm'],
            'class' => \F4\Core\ResponseEmitter\Html::class,
        ],
        'application/json' => [
            'extensions' => ['.json'],
            'class' => \F4\Core\ResponseEmitter\Json::class,
        ],
        // this emitter only supports command-line invokation
        Core\ResponseEmitter\Cli::INTERNAL_MIME_TYPE => [
            'extensions' => [],
            'class' => \F4\Core\ResponseEmitter\Cli::class,
        ],
    ];

    public const bool SESSION_ENABLED = true;
    public const string SESSION_COOKIE_NAME = 'F4_SESSION_ID';
    public const ?string SESSION_DOMAIN = null;
    public const bool SESSION_HTTP_ONLY = false;
    public const string SESSION_LIFETIME = '';
    public const string SESSION_PATH = '/';
    public const string SESSION_SAME_SITE = 'Strict';
    public const bool SESSION_SECURE_ONLY = true;
    // public const ?string SESSION_HANDLER = null;
    // public const bool SESSION_IGNORE_IP_ADDRESS = false;

    public const string TIMEZONE = '';

    public const array DICTIONARIES = [];
    public const string DEFAULT_LANGUAGE = 'en';

    public const string CORE_ROUTER_CLASS = Router::class;
    // public const string CORE_LOCALIZER_CLASS = Localizer::class;
    public const string CORE_API_PROXY_CLASS = CoreApiProxy::class;
    public const string DEFAULT_TEMPLATE = __DIR__ . '/../../templates/it-worked.pug';

}

