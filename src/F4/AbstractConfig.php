<?php

declare(strict_types = 1);

namespace F4;

use F4\Config\SensitiveParameter;

// use F4\DB\Adapter\PostgreSQL;
// use F4\DB\Adapter\PostgreSQL\TypeCaster;
// use F4\Phug\Renderer as PhugTemplateRenderer;

// use F4\Core\Dispatcher;
// use F4\Core\Localizer;
// use F4\Core\Router;

abstract class AbstractConfig
{

    // public const jsonapimode = true;
    // public const jsonparameters = false;

    public const bool DEBUG_MODE = false;
    public const bool DEBUG_EXTENDED_ERROR_OUTPUT = false;

    public const string DB_HOST = 'localhost';
    public const string DB_CHARSET = 'UTF8';
    public const string DB_PORT = '5432';
    public const string DB_NAME = '';
    public const string DB_USERNAME = '';
    #[SensitiveParameter]
    public const string DB_PASSWORD = '';
    public const string DB_SCHEMA = '';
    public const ?string DB_APP_NAME = null;
    public const bool DB_TRACE = false;
    public const bool DB_ALLOW_UNSAFE_RAW_QUERIES = false;
    // public const string DB_ADAPTER_CLASS = PostgreSQL::class;
    public const string DB_ADAPTER_ALIAS = '\F4\DB';

    // public const string DB_TYPE_CASTER_CLASS = TypeCaster::class;

    public const bool DB_PERSIST = true;
    public const bool DB_KEEP_NULLS = true;

    public const array MODULES = [];

    public const bool TEMPLATE_CACHE_ENABLED = false;
    public const string TEMPLATE_CACHE_PATH = '/tmp';
    public const int TEMPLATE_CACHE_LIFETIME = 3600;
    public const bool TEMPLATE_RELATIVE_PATHS = true; // search current path in a template for includes and mixins, only applies to Phug renderer
    public const array TEMPLATE_PATHS = [
    ];
    // public const string TEMPLATE_RENDERER_CLASS = PhugTemplateRenderer::class;

    public const string RESPONSE_CHARSET = 'utf-8';
    public const string FILESYSTEM_CHARSET = 'utf-8';

    public const string DEFAULT_OUTPUT_FORMAT = 'html'; // renderer must be set in OUTPUT_RENDERERS

    public const array OUTPUT_RENDERERS = [
        // 'html'      => Core\Renderer\Html::class,
        // 'json'      => Core\Renderer\Json::class,
        // 'txt'       => Core\Renderer\Text::class,
        // 'text'      => Core\Renderer\Text::class,
        // 'xml'      => Core\Renderer\Xml::class,
        // these are special formats used for development perposes only, their availability depends on debug mode setting
        // 'raw'       => self::DEBUG_MODE ? Core\Renderer\DebugRaw::class : null,
        // 'routes'    => self::DEBUG_MODE ? Core\Renderer\DebugRoutes::class : null,
        // 'trace'     => self::DEBUG_MODE ? Core\Renderer\DebugTrace::class : null,
        // 'test'      => self::DEBUG_MODE ? Core\Renderer\DebugTest::class : null
    ];

    public const string SESSION_COOKIE_NAME = 'f4';
    public const ?string SESSION_DOMAIN = null;
    public const ?string SESSION_HANDLER = null;
    public const bool SESSION_HTTP_ONLY = false;
    public const bool SESSION_REQUIRED = true;
    public const string SESSION_LIFETIME = '';
    public const string SESSION_PATH = '/';
    public const string SESSION_SAME_SITE = 'Strict';
    public const bool SESSION_SECURE_ONLY = true;
    // public const bool SESSION_IGNORE_IP_ADDRESS = false;

    public const string TIMEZONE = '';

    public const array DICTIONARIES = [];
    public const string DEFAULT_LANGUAGE = 'en';

    // public const string CORE_DISPATCHER_CLASS = Dispatcher::class;
    // public const string CORE_ROUTER_CLASS = Router::class;
    // public const string CORE_LOCALIZER_CLASS = Localizer::class;

    public const bool APPEND_FORMAT_SUFFIX_ON_REDIRECT = true;

}

