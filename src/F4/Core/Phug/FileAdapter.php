<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use F4\Config;
use Phug\Renderer;
use Phug\Renderer\Adapter\FileAdapter as BaseFileAdapter;

use RuntimeException;
use Exception;

class FileAdapter extends BaseFileAdapter
{

    public function __construct(Renderer $renderer, $options)
    {
        parent::__construct($renderer, $options);
        $options['cache_lifetime'] = $options['cache_lifetime'] ?? null;
        $options['tmp_dir'] = Config::TEMPLATE_CACHE_PATH ?: sys_get_temp_dir();
        $this->setOptions($options);
    }

    public function cache($path, $input, $rendered, &$success = null): string
    {
        $cacheFolder = $this->getCacheDirectory();
        $destination = $path;
        if (!$this->isCacheUpToDate(path: $destination, input: $input)) {
            if (!is_writable(filename: $cacheFolder)) {
                throw new RuntimeException(message: sprintf(format: 'Cache directory must be writable. "%s" is not.', values: $cacheFolder), code: 6);
            }
            $compiler = $this->getRenderer()->getCompiler();
            $fullPath = $compiler->locate($path) ?: $path;
            $output = $rendered($fullPath, $input);
            $importsPaths = $compiler->getImportPaths($fullPath);
            $success = $this->cacheFileContents(
                $destination,
                $output,
                $importsPaths
            );
        }
        return $destination;
    }

    private function isCacheUpToDate(&$path, $input = null): bool
    {
        if (!$input) {
            $registryPath = $this->getRegistryPath(path: $path);
            if ($registryPath !== false) {
                $path = $registryPath;
                return true;
            }
            return $this->checkPathExpiration(path: $path);
        }
        $path = $this->getCachePath(name: $this->hashPrint($input));
        $fileExists = file_exists(filename: $path);
        $fileExpired = ($fileExists && ($this->getOption('cache_lifetime') !== null))
            ? (time() - filemtime(filename: $path)) > $this->getOption('cache_lifetime')
            : false;

        return $fileExists && !$fileExpired;
    }

    protected function createTemporaryFile()
    {
        // suppress notice
        if (
            false === ($filename = @call_user_func(
                $this->getOption('tmp_name_function'),
                $this->getOption('tmp_dir'),
                'pug'
            ))
        ) {
            throw new Exception(message: 'temporary-file-creation-failed');
        }
        return $filename;
    }

    // unmodified private methods

    private function getRawCachePath(string $file): string
    {
        $cacheDir = $this->getCacheDirectory();
        return str_replace('//', '/', $cacheDir . '/' . $file);
    }

    private function getCachePath(string $name): string
    {
        return $this->getRawCachePath(file: "{$name}.php");
    }

    private function hasExpiredImport(string $sourcePath, string $cachePath): bool
    {
        $importsMap = $cachePath . '.imports.serialize.txt';
        if (!file_exists(filename: $importsMap)) {
            return true;
        }
        $importPaths = unserialize(file_get_contents(filename: $importsMap)) ?: [];
        $importPaths[] = $sourcePath;
        $time = filemtime(filename: $cachePath);
        foreach ($importPaths as $importPath) {
            if (!file_exists(filename: $importPath) || filemtime(filename: $importPath) >= $time) {
                // If only one file has changed, expires
                return true;
            }
        }
        // If only no files changed, it's up to date
        return false;
    }

    private function getRegistryPath(string $path, array $extensions = []): string|false
    {
        if ($this->getOption('up_to_date_check')) {
            return false;
        }

        if ($this->hasOption('extensions')) {
            $extensions = array_merge($extensions, $this->getOption('extensions'));
        }

        $cachePath = $this->findCachePathInRegistryFile($path, $this->getCachePath(name: 'registry'), $extensions);

        if ($cachePath) {
            return $this->getRawCachePath(file: $cachePath);
        }

        return false;
    }

    private function checkPathExpiration(string &$path): bool
    {
        $compiler = $this->getRenderer()->getCompiler();
        $input = $compiler->resolve($path);
        $path = $this->getCachePath(
            name: ($this->getOption('keep_base_name') ? basename(path: $path) : '') .
            $this->hashPrint($input)
        );

        // If up_to_date_check never refresh the cache
        if (!$this->getOption('up_to_date_check')) {
            return true;
        }

        // If there is no cache file, create it
        if (!file_exists(filename: $path)) {
            return false;
        }

        // Else check the main input path and all imported paths in the template
        return !$this->hasExpiredImport(sourcePath: $input, cachePath: $path);
    }

    private function getCacheDirectory()
    {
        $cacheFolder = $this->hasOption('cache_dir')
            ? $this->getOption('cache_dir')
            : null;
        if (!$cacheFolder && $cacheFolder !== false) {
            $cacheFolder = $this->getRenderer()->hasOption('cache_dir')
                ? $this->getRenderer()->getOption('cache_dir')
                : null;
        }
        if ($cacheFolder === true) {
            $cacheFolder = $this->getOption('tmp_dir');
        }

        if (!is_dir(filename: $cacheFolder) && !@mkdir(directory: $cacheFolder, permissions: 0777, recursive: true)) {
            throw new RuntimeException(
                message: $cacheFolder . ': Cache directory doesn\'t exist.' . "\n" .
                'Create it with:' . "\n" .
                'mkdir -p ' . escapeshellarg(arg: realpath(path: $cacheFolder)) . "\n" .
                'Or replace your cache setting with a valid writable folder path.',
                code: 5
            );
        }

        return $cacheFolder;
    }

}
