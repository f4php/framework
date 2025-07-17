<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use F4\Config;
use F4\Core\Phug\{FluentResourceModule, StripViteResourceModule, ViteBundleModule};

use Phug\Phug;
use Phug\Component\ComponentExtension;
use Phug\Optimizer;

use function fopen;
use function sys_get_temp_dir;

class TemplateRenderer
{
    public function __construct(protected $options = [])
    {
        // workaround for undefined stdout
        if (!defined(constant_name: 'STDOUT')) {
            define(constant_name: 'STDOUT', value: fopen(filename: 'php://stdout', mode: 'wb'));
        }
        Phug::setOptions(options: [
            ...[
                'debug' => Config::DEBUG_MODE,
                'exit_on_error' => Config::DEBUG_MODE,
                'cache_dir' => (Config::DEBUG_MODE && !Config::TEMPLATE_CACHE_ENABLED) ? null : (Config::TEMPLATE_CACHE_PATH ?: sys_get_temp_dir()),
                'paths' => self::getPaths(),
                'enable_profiler' => false,
                'memory_limit' => -1,
                'execution_max_time' => -1,
                'modules' => [
                    FluentResourceModule::class,
                    ViteBundleModule::class,
                    StripViteResourceModule::class,
                ],
            ],
            ...$options
        ]);
        ComponentExtension::enable();
    }
    public function displayFile(string $file, array $args = []): void
    {
        if (Config::DEBUG_MODE === true) {
            Phug::displayFile(path: $file, parameters: $args);
        } else {
            Optimizer::call('displayFile', [$file, $args]);
        }
    }
    public static function getPaths(?array $paths = null): array
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

