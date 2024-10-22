<?php

declare(strict_types = 1);

namespace F4;

use F4\DB\Adapter\PostgreSQL;
use F4\DB\Adapter\PostgreSQL\TypeCaster;
// use F4\Phug\Renderer as PhugTemplateRenderer;

use F4\Core\Dispatcher;
use F4\Core\Localizer;
use F4\Core\Router;

abstract class AbstractConfig
{

    // const jsonapimode = true;
    // const jsonparameters = false;

    const bool DEBUG_MODE = true;
    const bool DEBUG_EXTENDED_ERROR_OUTPUT = false;

    const string DB_HOST = 'localhost';
    const string DB_CHARSET = 'UTF8';
    const string DB_PORT = '5432';
    const string DB_NAME = '';
    const string DB_USERNAME = '';
    const string DB_PASSWORD = '';
    const string DB_SCHEMA = '';
    const ?string DB_APP_NAME = null;
    const bool DB_TRACE = false;
    const bool DB_ALLOW_UNSAFE_RAW_QUERIES = false;
    const string DB_ADAPTER_CLASS = PostgreSQL::class;
    const string DB_ADAPTER_ALIAS = '\F4\DB';

    const string DB_TYPE_CASTER_CLASS = TypeCaster::class;

    const bool DB_PERSIST = true;
    const bool DB_KEEP_NULLS = true;

    const array MODULES = [];

    const bool TEMPLATE_CACHE_ENABLED = false;
    const string TEMPLATE_CACHE_PATH = '/tmp';
    const int TEMPLATE_CACHE_LIFETIME = 3600;
    const bool TEMPLATE_RELATIVE_PATHS = true; // search current path in a template for includes and mixins, only applies to Phug renderer
    const array TEMPLATE_PATHS = [
    ];
    // const string TEMPLATE_RENDERER_CLASS = PhugTemplateRenderer::class;

    const string RESPONSE_CHARSET = 'utf-8';
    const string FILESYSTEM_CHARSET = 'utf-8';

    const string DEFAULT_OUTPUT_FORMAT = 'html'; // renderer must be set in OUTPUT_RENDERERS

    const array OUTPUT_RENDERERS = [
        'html'      => Core\Renderer\Html::class,
        'json'      => Core\Renderer\Json::class,
        'txt'       => Core\Renderer\Text::class,
        'text'      => Core\Renderer\Text::class,
        // 'xml'      => Core\Renderer\Xml::class,
        // these are special formats used for development perposes only, their availability depends on debug mode setting
        'raw'       => self::DEBUG_MODE ? Core\Renderer\DebugRaw::class : null,
        'routes'    => self::DEBUG_MODE ? Core\Renderer\DebugRoutes::class : null,
        'trace'     => self::DEBUG_MODE ? Core\Renderer\DebugTrace::class : null,
        'test'      => self::DEBUG_MODE ? Core\Renderer\DebugTest::class : null
    ];

    const string SESSION_COOKIE_NAME = 'f4';
    const ?string SESSION_DOMAIN = null;
    const ?string SESSION_HANDLER = null;
    const bool SESSION_HTTP_ONLY = false;
    const string SESSION_REQUIRED = true;
    const string SESSION_LIFETIME = '';
    const string SESSION_PATH = '/';
    const string SESSION_SAME_SITE = 'Strict';
    const bool SESSION_SECURE_ONLY = true;
    // const bool SESSION_IGNORE_IP_ADDRESS = false;

    const string TIMEZONE = '';

    const array DICTIONARIES = [];
    const string DEFAULT_LANGUAGE = 'en';

    const string CORE_DISPATCHER_CLASS = Dispatcher::class;
    const string CORE_ROUTER_CLASS = Router::class;
    const string CORE_LOCALIZER_CLASS = Localizer::class;

    const bool APPEND_FORMAT_SUFFIX_ON_REDIRECT = true;

}

