<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Loader;

use function array_map;
use function is_array;

trait AssetManifestAwareTrait
{
    public static function getManifestData(string $entryPoint, string $property, bool $prependPath = true, ?string $path = null): string|array|null
    {
        $entries = Loader::getAssetsManifest(path: $path);
        $entry = $entries[$entryPoint][$property] ?? null;
        return match(is_array($entry)) {
            true => array_map(function($entry) use ($prependPath) {
                return $entry ? (($prependPath?Loader::getAssetPath():'') . $entry) : null;
            }, $entry),
            default => $entry ? (($prependPath?Loader::getAssetPath():'') . $entry) : null
        };
    }
}