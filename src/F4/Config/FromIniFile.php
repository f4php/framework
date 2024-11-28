<?php

declare(strict_types=1);

namespace F4\Config;

use Attribute;
use ErrorException;
use F4\Config\ConfigAttribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class FromIniFile extends ConfigAttribute
{

    public const string DEFAULT_PATH = "settings.ini";

    protected string $name = '';
    protected string $file = '';
    protected string $path = '';

    public function __construct(string $name, ?string $file = null, ?string $path = null)
    {
        $this->name = $name;
        if($file) {
            $this->file = $file;
        }
        if($path) {
            $this->path = $path;
        }
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getValue(): mixed 
    {
        $filename = $this->path.($this->file ?: self::DEFAULT_PATH);
        if ((($parsedFile = parse_ini_file(filename: $filename, process_sections: false, scanner_mode: INI_SCANNER_TYPED)) === false) && !empty($this->file)) {
            throw new ErrorException(message: "Could not parse ini file {$this->file}");
        }
        return $parsedFile[$this->name] ?? null;
    }

}