<?php

namespace F4\Config;

use Attribute;
use ErrorException;
use F4\Config\ConfigAttribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class FromIniFile extends ConfigAttribute
{

    public const string DEFAULT_PATH = "settings.ini";

    public function __construct(string $name, ?string $file = null)
    {
        if ((($parsedFile = parse_ini_file(filename: $file ?: self::DEFAULT_PATH, process_sections: false, scanner_mode: INI_SCANNER_NORMAL)) === false) && !empty($file)) {
            throw new ErrorException(message: "Could not parse ini file {$file}");
        }
        $this->value = $parsedFile[$name] ?? null;
    }

}