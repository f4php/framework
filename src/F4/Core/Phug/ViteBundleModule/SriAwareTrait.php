<?php

declare(strict_types=1);

namespace F4\Core\Phug\ViteBundleModule;

use InvalidArgumentException;
use RuntimeException;

use function array_diff;
use function array_filter;
use function array_map;
use function base64_encode;
use function file_get_contents;
use function hash;
use function implode;
use function realpath;
use function sprintf;

trait SriAwareTrait
{
    protected const array SUPPORTED_SRI_ALGORITHMS = [
        'sha256',
        'sha384',
        'sha512',
    ];
    protected const string DEFAULT_SRI_ALGORITHM = 'sha384';
    public static function generateSri(string $path, string|array $algorithms = self::DEFAULT_SRI_ALGORITHM): string
    {
        $algorithms = array_filter((array) $algorithms) ?: [self::DEFAULT_SRI_ALGORITHM];
        if ($unsupportedAlgorithms = array_diff($algorithms, self::SUPPORTED_SRI_ALGORITHMS)) {
            throw new InvalidArgumentException(sprintf("Unsupported SRI algorithm(s): %s", implode(separator: ', ', array: $unsupportedAlgorithms)));
        }
        if (false === ($realpath = realpath($path)) || false === ($content = file_get_contents($realpath))) {
            throw new RuntimeException("Failed to read file for SRI: {$path}");
        }
        return implode(
            separator: ' ',
            array: array_map(
                callback: fn(string $algorithm): string =>
                sprintf(
                    '%s-%s',
                    $algorithm,
                    base64_encode(
                        hash(
                            algo: $algorithm,
                            data: $content,
                            binary: true,
                        ),
                    ),
                ),
                array: $algorithms,
            ),
        );
    }
}