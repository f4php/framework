<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use ErrorException;
use F4\Core\Phug\Util;
use Phug\Compiler\LocatorInterface;

class FileLocator implements LocatorInterface
{

    private function normalize($path): string
    {
        if (is_array(value: $path)) {
            throw new ErrorException(message: 'path-cannot-be-array');
        }
        return rtrim(string: str_replace(search: '\\', replace: '/', subject: $path), characters: '/');
    }

    private function getFullPath($location, $path, $extension): string|false
    {
        $fullPath = "$location/$path$extension";
        if (@is_file(filename: $fullPath) && is_readable(filename: $fullPath)) {
            return realpath(path: $fullPath);
        }
        $length = strlen(string: $extension);
        if (
            $length && substr(string: $path, offset: -$length) === $extension && @is_file(filename: $fullPath = "$location/$path") && is_readable(filename: $fullPath)
        ) {
            return realpath(path: $fullPath);
        }
        return false;
    }

    public function locate($path, array $locations, array $extensions): string|false
    {
        if(is_null($path)) {
            return false;
        }
        if (@is_file(filename: $path)) {
            return is_readable(filename: $path) ? realpath(path: $path) : false;
        }
        $path = ltrim(string: $this->normalize(path: $path), characters: '/');
        $locations = Util::getPaths(paths: $locations);
        foreach ($locations as $location) {
            if ($location !== false) {
                $location = $this->normalize(path: $location);
                foreach ($extensions as $extension) {
                    if ($fullPath = $this->getFullPath(location: $location, path: $path, extension: $extension)) {
                        return $fullPath ?: false;
                    }
                }
            }
        }
        return false;
    }

}

