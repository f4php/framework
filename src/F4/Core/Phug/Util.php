<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use F4\Config;

class Util
{

    public static function getPaths(array $paths = null): array
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

