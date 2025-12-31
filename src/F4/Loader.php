<?php

declare(strict_types=1);

namespace F4;

use ErrorException;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionNamedType;

use F4\AbstractConfig;
use F4\Config\FromEnvironmentVariable;
use F4\Config\FromIniFile;
use F4\Config\SensitiveParameter;
use F4\Config\SensitiveParameterKey;

use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Nette\PhpGenerator\Constant;

use function file_exists;
use function file_get_contents;
use function is_array;
use function json_decode;
use function mb_substr;

class Loader
{

    public const array DEFAULT_ENVIRONMENTS = ['local', 'default'];

    private static string $path = __DIR__ . '/../../'; // project root, should be updated with application project root via ::setPath()
    private static string $publicPath = __DIR__ . '/../../public'; // project public root, should be updated with application project root via ::setPublicPath()
    private static string $assetPath = '/assets/';
    private static string $currentEnvironment;

    public static function setPath(string $path): void
    {
        $path .= mb_substr(string: $path, start: -1) === '/' ? '' : '/';
        self::$path = $path;
    }
    public static function setPublicPath(string $publicPath): void
    {
        $publicPath .= mb_substr(string: $publicPath, start: -1) === '/' ? '' : '/';
        self::$publicPath = $publicPath;
    }
    public static function getPath(): string
    {
        return self::$path;
    }
    public static function getPublicPath(): string
    {
        return self::$publicPath;
    }
    public static function setAssetPath(string $path): void
    {
        $path .= mb_substr(string: $path, start: -1) === '/' ? '' : '/';
        self::$assetPath = $path;
    }
    public static function getAssetPath(): string
    {
        return self::$assetPath;
    }
    public static function getCurrentEnvironment(): string
    {
        return self::$currentEnvironment;
    }
    public static function getEnvironments(): array
    {
        if (!$fileContents = file_get_contents(filename: self::$path . "/composer.json")) {
            throw new ErrorException('Failed to locate composer.json file');
        }
        $composerConfiguration = json_decode(json: $fileContents, associative: true, flags: JSON_THROW_ON_ERROR);
        return $composerConfiguration['extra']['f4']['environments'] ?? [] ?: [];
    }
    public static function loadEnvironmentConfig(array $environments = self::DEFAULT_ENVIRONMENTS): void
    {
        $configuredEnvironments = static::getEnvironments();
        foreach ($environments as $environment) {
            if (isset($configuredEnvironments[$environment])) {
                $filename = $configuredEnvironments[$environment]['config'] ?? null;
                if ($filename && file_exists(filename: self::$path . $filename)) {
                    self::$currentEnvironment = $environment;
                    require_once self::$path . $filename;
                    return;
                }
            }
        }
        throw new ErrorException(message: 'Cannot load configuration file');
    }
    public static function generateConfigurationFile(string $templateClassName = \F4\Config::class, ?string $comment = null, string $targetNamespace = __NAMESPACE__, string $targetClassName = 'Config', bool $stripSensitiveData = true): string
    {
        $file = new PhpFile();
        if ($comment) {
            $file->addComment($comment);
        }
        $configNamespace = $file->addNamespace(new PhpNamespace($targetNamespace));
        $configNamespace->addUse(SensitiveParameter::class);
        $configClass = $configNamespace->addClass($targetClassName);
        $configClass->setExtends(AbstractConfig::class);
        $reflectionClass = new ReflectionClass(objectOrClass: $templateClassName);
        if ($reflectionClassConstants = $reflectionClass->getReflectionConstants()) {
            foreach ($reflectionClassConstants as $reflectionClassConstant) {
                if ($reflectionClassConstant->getModifiers() === ReflectionClassConstant::IS_PUBLIC) {
                    $configConstantName = $reflectionClassConstant->getName();
                    $cofigConstantType = null;
                    $configConstant = new Constant($configConstantName);
                    $configConstant->setPublic();
                    if ($reflectionClassConstant->hasType()) {
                        match (($cofigConstantType = $reflectionClassConstant->getType()) instanceof ReflectionNamedType) {
                            true => $configConstant->setType(($cofigConstantType->allowsNull() ? '?' : '') . $cofigConstantType->getName()),
                            default => throw new ErrorException("Class constant {$configConstantName} uses an unsupported type")
                        };
                    }
                    $constantAttributes = [];
                    $constantComments = [];
                    $constantValue = $reflectionClassConstant->getValue();
                    if ($reflectionClassConstant->getAttributes(name: SensitiveParameter::class, flags: ReflectionAttribute::IS_INSTANCEOF)) {
                        $constantAttributes[SensitiveParameter::class] = [];
                        if ($cofigConstantType && $stripSensitiveData) {
                            $constantValue = $cofigConstantType->allowsNull() ? null : '';
                            $constantComments[] = "Default value for {$configConstantName} was stripped as sensitive";
                        }
                    }
                    if ($stripSensitiveData && is_array(value: $constantValue) && ($reflectionAttributesSensitiveKey = $reflectionClassConstant->getAttributes(name: SensitiveParameterKey::class, flags: ReflectionAttribute::IS_INSTANCEOF))) {
                        foreach ($reflectionAttributesSensitiveKey as $reflectionAttribute) {
                            $attributeInstance = $reflectionAttribute->newInstance();
                            $sensitiveKey = $attributeInstance->getValue();
                            unset($constantValue[$sensitiveKey]);
                            $constantComments[] = "Key {$sensitiveKey} was stripped as sensitive";
                        }
                    }
                    if ($reflectionAttributesFromEnvironment = $reflectionClassConstant->getAttributes(name: FromEnvironmentVariable::class, flags: ReflectionAttribute::IS_INSTANCEOF)) {
                        foreach ($reflectionAttributesFromEnvironment as $reflectionAttribute) {
                            $attributeInstance = $reflectionAttribute->newInstance();
                            $constantValue = $attributeInstance->getValue();
                            $arguments = $reflectionAttribute->getArguments();
                            $constantComments[] = 'Populated from ' . $arguments['name'] ?? $arguments[0] . ' environment variable';
                        }
                    }
                    if ($reflectionAttributesFromIni = $reflectionClassConstant->getAttributes(name: FromIniFile::class, flags: ReflectionAttribute::IS_INSTANCEOF)) {
                        foreach ($reflectionAttributesFromIni as $reflectionAttribute) {
                            $attributeInstance = $reflectionAttribute->newInstance();
                            $attributeInstance->setPath(__DIR__ . '/../config/');
                            $constantValue = $attributeInstance->getValue();
                            $arguments = $reflectionAttribute->getArguments();
                            $constantComments[] = 'Populated from ' . ($arguments['name'] ?? $arguments[0]) . ' in ' . (($arguments['file'] ?? $arguments[1] ?? null) ?: FromIniFile::DEFAULT_PATH) . ' file';
                        }
                    }
                    foreach ($constantAttributes as $constantAttributeName => $constantAttributeExtras) {
                        $configConstant->addAttribute($constantAttributeName, $constantAttributeExtras);
                    }
                    foreach ($constantComments as $constantComment) {
                        $configConstant->addComment($constantComment);
                    }
                    $configConstant->setValue($constantValue);
                    $configClass->addMember($configConstant);
                }
            }
        }
        return (new PsrPrinter)->printFile($file);
    }

    public static function getAssetsManifest(?string $path = null): array
    {
        $filename = self::$path . '/public' . self::$assetPath . ($path ?? '.vite/manifest.json');
        if (!file_exists($filename)) {
            throw new ErrorException('Cannot locate vite manifest file, try running `npm run build` in project root');
        }
        return json_decode(json: file_get_contents(filename: $filename), associative: true, flags: JSON_THROW_ON_ERROR);
    }

}