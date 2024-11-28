<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use F4\Config;

use Phug\Renderer;
use Phug\Component\ComponentExtension;

class TemplateRenderer extends Renderer 
{

    public function __construct(array $options = [])
    {
        // workaround for undefined stdout
        if (!defined(constant_name: 'STDOUT')) {
            define(constant_name: 'STDOUT', value: fopen(filename: 'php://stdout', mode: 'wb'));
        }
        parent::__construct(options: [...[
            'debug'                 => Config::DEBUG_MODE,
            'exit_on_error'         => Config::DEBUG_MODE,
            'pretty'                => false,
            'cache_dir'             => (Config::DEBUG_MODE && !Config::TEMPLATE_CACHE_ENABLED) ? null : (Config::TEMPLATE_CACHE_PATH ?: sys_get_temp_dir()),
            'cache_lifetime'        => (Config::DEBUG_MODE && !Config::TEMPLATE_CACHE_ENABLED) ? null : Config::TEMPLATE_CACHE_LIFETIME,
            'paths'                 => self::getPaths()
        ], ...$options]);
        ComponentExtension::enable(renderer: $this);
    }

    public function renderTemplate(string $file, array $args = []): string
    {
        return $this->renderFile(path: $file, parameters: $args);
    }

    protected static function getPaths(?array $paths = null): array
    {
        $paths = Config::TEMPLATE_RELATIVE_PATHS ? (array) $paths : [];
        if (Config::TEMPLATE_PATHS) {
            $paths = array_merge((array) $paths, Config::TEMPLATE_PATHS);
        }
        $realpaths = [];
        foreach ((array) $paths as $i => $path) {
            if (realpath(path: mb_trim(string: $path))) {
                $realpaths[] = realpath(path: $path);
            }
        }
        return $realpaths;
    }

}

