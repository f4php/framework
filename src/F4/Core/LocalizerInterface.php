<?php

declare(strict_types=1);

namespace F4\Core;

interface LocalizerInterface
{
    public function addFtl(string $resource, bool $allowOverrides = false): void;
    public function addResource(string $resource, bool $allowOverrides = false): void;
    public function getLocale(): string;
    public function getResources(): array;
    public function getTranslateFunction(): callable;
    public function setLocale(string $locale, array $options = []): void;
}