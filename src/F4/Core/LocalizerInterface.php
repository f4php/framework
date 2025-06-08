<?php

declare(strict_types=1);

namespace F4\Core;

interface LocalizerInterface
{
    public function addResource(string $resource): void;
    public function getLocale(): string;
    public function getTranslateFunction(): callable;
    public function setLocale(string $locale): void;
}