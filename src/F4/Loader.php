<?php

declare(strict_types=1);

namespace F4;

use ErrorException;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;

use F4\AbstractConfig;
use F4\Config\FromEnvironmentVariable;
use F4\Config\FromIniFile;
use F4\Config\SensitiveParameter;
use F4\Config\SensitiveParameterKey;

use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Nette\PhpGenerator\Constant;

use function json_decode;
use function file_get_contents;
use function file_exists;
use function mb_substr;

class Loader
{

    public const array DEFAULT_ENVIRONMENTS = ['local', 'default'];

    static private string $path = __DIR__ . '/../../'; // project root, should be replaced with application project root via ::setPath()

    public static function setPath(string $path): void
    {
        $path .= mb_substr(string: $path, start: -1) == '/' ? '' : '/';
        self::$path = $path;
    }
    public static function getEnvironments(): array
    {
        if(!$fileContents = file_get_contents(filename: self::$path . "/composer.json")) {
            throw new ErrorException('Faile to locate composer.json file');
        }
        $composerConfiguration = json_decode(json: $fileContents, associative: true, flags: JSON_THROW_ON_ERROR);
        return $composerConfiguration['extra']['f4']['environments'] ?? [] ?: [];
    }
    public static function loadEnvironmentConfig(array $environments = self::DEFAULT_ENVIRONMENTS): void
    {
        $configuredEnvironments = static::getEnvironments();
        foreach ($environments as $environment) {
            if (isset($configuredEnvironments[$environment])) {
                $filename = $configuredEnvironments[$environment];
                if ($filename && file_exists(filename: self::$path . $filename)) {
                    require_once self::$path . $filename;
                    return;
                }
            }
        }
        throw new ErrorException(message: 'cannot load configuration file');
    }
    protected static function stripSensitiveDataFromArrayValue(ReflectionClassConstant &$reflectionConstant): void
    {
        // todo
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
                    $configConstant = new Constant($reflectionClassConstant->getName());
                    $configConstant->setPublic();
                    // todo implement better reflectiontype once the docs are updated
                    if ($reflectionClassConstant->hasType()) {
                        $configConstant->setType(($reflectionClassConstant->getType()->allowsNull() ? 'null|' : '') . $reflectionClassConstant->getType()->getName());
                    }
                    $constantAttributes = [];
                    $constantComments = [];
                    $constantValue = $reflectionClassConstant->getValue();
                    if ($reflectionClassConstant->getAttributes(name: SensitiveParameter::class, flags: ReflectionAttribute::IS_INSTANCEOF)) {
                        $constantAttributes[SensitiveParameter::class] = [];
                        if ($stripSensitiveData) {
                            $constantValue = null;
                            $constantComments[] = 'Default value was stripped as sensitive';
                        }
                    }
                    if ($stripSensitiveData && \is_array(value: $constantValue) && ($reflectionAttributesSensitiveKey = $reflectionClassConstant->getAttributes(name: SensitiveParameterKey::class, flags: ReflectionAttribute::IS_INSTANCEOF))) {
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

}