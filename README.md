# About

This is f4, a lightweight PHP/PostgreSQL-based web application development framework.

# Quick start

To quickly start a new application, you should refer to [f4php/f4](https://github.com/f4php/f4) package, which works as a self-documented skeletal application.

In order to create your own project using [composer](https://getcomposer.org/), use the following command:

```
composer create-project f4php/f4
```

and then follow [official documentation](https://github.com/f4php/f4).

# Developer's guide

## Introduction

F4 is a very lightweight framework, it provides a developer with minimal web app infrastructure and tries its best to let you focus on application-specific logic.

## Architecture

F4 framework architecture includes several key elements, but most of the developer-facing features are documented in a [f4php/f4](https://github.com/f4php/f4) package. If you just want to try coding with F4, definitely start there.

The main feature that is not documented elsewhere is the explanation of F4 bootstrap process, which is provided below.

### F4\Loader and F4\Core

F4's core philosophy is based on the idea that it controls (or takes over) the processing of any incoming HTTP request, and allows app developer to focus on a (mostly) declarative description of how the request should be handled. 

F4 uses its own bootstrap process to initialize itself and collect any app-specific information (coming in the form of `Module`'s') before passing instances of `Request` and `Response` to app-specific `Route`'s. It also provides a flexible PostgreSQL-compatible database query builder as a `DB` class.

Code below is the only part that is placed in a web server root folder (`public/index.php` by default).

Each bootstrap phase supports custom handlers that allow low-level intervention. It is highly unlikely that your project would ever require this feature, but it's there if you need it.

```php
<?php

declare(strict_types=1);

use F4\Loader;
use F4\Core;

require_once __DIR__.'/../vendor/autoload.php';

Loader::setPath(path: __DIR__ . '/../');
Loader::loadEnvironmentConfig(environments: [($_SERVER['F4_ENVIRONMENT']??null)?:'local', 'default']);

(new Core(/*
        $alternativeRouterClassName,
        $alternativeCoreApiProxyClassName
    */))
    ->setUpRequestResponse(
        /*
        function($defaultHandler) {
            // This place is for dirty hacks only, please refer to class structure for better customization options
            // $this refers to Core instance
            $defaultHandler();
        }
    */)
    ->setUpEnvironment(
        /*
        function($defaultHandler) {
            // This place is for dirty hacks only, please refer to class structure for better customization options
            // $this refers to Core instance
            $defaultHandler();
        }
    */)
    ->registerModules(/*
        function($defaultHandler) {
            // This place is for dirty hacks only, please refer to class structure for better customization options
            // $this refers to Core instance
            $defaultHandler();
        }
    */)
    ->processRequest(/*
        function($defaultHandler) {
            // This place is for dirty hacks only, please refer to class structure for better customization options
            // $this refers to Core instance
            $defaultHandler();
        }
    */)
    ->emitResponse(/*
        function($defaultHandler) {
            // This place is for dirty hacks only, please refer to class structure for better customization options
            // $this refers to Core instance
            $defaultHandler();
        }
    */)
    ->restoreEnvironment(/*
        function($defaultHandler) {
            // This place is for dirty hacks only, please refer to class structure for better customization options
            // $this refers to Core instance
            $defaultHandler();
        }
    */)
    ;
```
This bootstrap sequence is mostly self-explanatory, and contains the following steps:

1) Setting Loader base path
2) Locating and loading main configuration file based on "environment"
   1) The list of environments is defined in `composer.json` file
   2) Selection of a specific environment for each request is based on `F4_ENVIRONMENT` process environment variable, which may come a bit confusing at first, but this gives DevOps engineers a tool to control how to bootstrap F4 on cloud servers
   3) Default environments values of 'local' and 'default' are used, in that particular order of preference, only if `F4_ENVIRONMENT` is not set
   4) Failure to locate and load the main configuration file will prevent F4 from bootstrapping, it will result in a fatal error
3) Creating a new instance of Core class
4) Instantiating and initializing internal Request and Response objects based on actual request data
5) Setting up code runtime environment like output buffer caching, custom exception handlers, internal multibyte encoding, timezone etc.
6) Registering modules. App-specific code is registered, but not yet run at this stage
7) Request is routed to app-specific code, actually running any applicable handlers
8) A response is emitted to the request initiator (normally, a web browser)
9) Code runtime environment is cleaned up

Custom intervention in the bootstrap process may be required if, for example, exotic third-party software needs to be initialized / configured at a certain stage and made available to other parts of the code.

Once again, normal operation does not require any developer intervention in the bootstrap process.

## Tests and static analysis

F4 framework package contains unit tests and supports static code analysis with phpstan, using the following commands:

```
composer run test
```
```
composer run phpstan
```

