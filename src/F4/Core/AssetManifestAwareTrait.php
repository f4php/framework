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
        return match (is_array($entry)) {
            true => array_map(
                callback: fn (string $entry): ?string =>
                    $entry ? (($prependPath ? Loader::getAssetPath() : '') . $entry) : null,
                array: $entry,
            ),
            default => $entry ? (($prependPath ? Loader::getAssetPath() : '') . $entry) : null,
        };
    }
}