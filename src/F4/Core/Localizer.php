<?php

declare(strict_types=1);

namespace F4\Core;

use InvalidArgumentException;

use F4\Config;
use F4\Core\LocalizerInterface;
use Major\Fluent\Bundle\FluentBundle;

use function array_keys;
use function array_map;
use function file_get_contents;
use function in_array;
use function sprintf;

class Localizer implements LocalizerInterface
{
    private string $locale;
    private FluentBundle $bundle;

    public function __construct(string $locale = Config::DEFAULT_LOCALE)
    {
        $this->setLocale($locale);
    }
    public function addResource(string $resource, bool $allowOverrides = false): void
    {
        $this->bundle->addFtl(file_get_contents($resource), $allowOverrides);
    }
    public function getLocale(): string
    {
        return $this->locale;
    }
    public function getTranslateFunction(): callable
    {
        return fn(string $message, array $arguments): string => $this->bundle->message($message, $arguments) ?: '';
    }
    public function setLocale(string $locale): void
    {
        if (!in_array(needle: $locale, haystack: array_keys(Config::LOCALES))) {
            throw new InvalidArgumentException(sprintf("Locale '%s' not found, please check your Config", $locale));
        }
        $this->locale = $locale;
        $this->bundle = new FluentBundle($locale, strict: true);
        array_map(fn($resource) => $this->addResource($resource), Config::LOCALES[$locale]['resources'] ?? []);
    }
}
