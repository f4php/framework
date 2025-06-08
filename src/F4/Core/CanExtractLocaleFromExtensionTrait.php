<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Config;

use function array_reduce;
use function in_array;

trait CanExtractLocaleFromExtensionTrait
{
    public function getAvailableLocaleExtensions(): array
    {
        return array_reduce(
            array: Config::LOCALES,
            callback: function ($extensions, $localeConfiguration): array {
                return [...$extensions, ...$localeConfiguration['extensions'] ?? []];
            },
            initial: [],
        );
    }
    protected function getLocaleFromExtension(string $extension): ?string
    {
        foreach (Config::LOCALES as $locale => $details) {
            if (in_array(needle: $extension, haystack: $details['extensions'])) {
                return $locale;
            }
        }
        return null;
    }

}