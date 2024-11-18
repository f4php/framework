<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Config;

trait CanExtractFormatFromExtensionTrait
{
    public function getAvailableExtensions(): array
    {
        return \array_reduce(
            array: Config::RESPONSE_RENDERERS,
            callback: function ($extensions, $rendererConfiguration): array {
                return [...$extensions, ...$rendererConfiguration['extensions'] ?? []];
            },
            initial: []
        );
    }
    protected function getResponseFormatFromExtension(string $extension): ?string
    {
        foreach (Config::RESPONSE_RENDERERS as $format => $details) {
            if (\in_array(needle: $extension, haystack: $details['extensions'])) {
                return $format;
            }
        }
        return null;
    }

    protected function getDebugFormatFromExtension(string $debugExtension): ?string
    {
        foreach (Config::RESPONSE_RENDERERS as $format => $details) {
            if ($debugExtension === ($details['debug-extension'] ?? null)) {
                return $format;
            }
        }
        return null;
    }

}