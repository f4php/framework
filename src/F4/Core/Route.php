<?php

declare(strict_types=1);

namespace F4\Core;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use Throwable;
use ValueError;

use Composer\Pcre\Preg;

use F4\Config;

use F4\HookManager;
use F4\Core\CanExtractFormatFromExtensionTrait;
use F4\Core\ExceptionHandlerTrait;
use F4\Core\MiddlewareAwareTrait;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\RouteInterface;
use F4\Core\Validator;

use function array_map;
use function array_keys;
use function array_walk;
use function implode;
use function in_array;
use function is_callable;
use function is_numeric;
use function preg_quote;

class Route implements RouteInterface
{
    use CanExtractFormatFromExtensionTrait;
    use ExceptionHandlerTrait;
    use MiddlewareAwareTrait;
    use StateAwareTrait;

    protected Closure $handler;
    protected ?string $name = null;
    protected array $templates = [
        Config::DEFAULT_RESPONSE_FORMAT => Config::DEFAULT_TEMPLATE
    ];
    protected array $exceptionHandlers = [];
    protected string $requestPathRegExp;
    protected ?string $responseFormatRegExp = null;

    public function __construct(string $pathDefinition, callable $handler)
    {
        [$this->requestPathRegExp, $extension] = $this->unpackPath(path: $pathDefinition);
        $this->responseFormatRegExp = match ($extension) {
            null => Config::STRICT_RESPONSE_FORMAT_MATCHING ? preg_quote(str: Config::DEFAULT_RESPONSE_FORMAT, delimiter: '/') : '.+',
            default => preg_quote(str: $this->getResponseFormatFromExtension(extension: $extension), delimiter: '/')
        };
        $this->setHandler(handler: $handler);
    }
    public static function get(string $pathDefinition, callable $handler): static
    {
        return new static("GET {$pathDefinition}", $handler);
    }
    public static function head(string $pathDefinition, callable $handler): static
    {
        return new static("HEAD {$pathDefinition}", $handler);
    }
    public static function post(string $pathDefinition, callable $handler): static
    {
        return new static("POST {$pathDefinition}", $handler);
    }
    public static function put(string $pathDefinition, callable $handler): static
    {
        return new static("PUT {$pathDefinition}", $handler);
    }
    public static function delete(string $pathDefinition, callable $handler): static
    {
        return new static("DELETE {$pathDefinition}", $handler);
    }
    public static function connect(string $pathDefinition, callable $handler): static
    {
        return new static("CONNECT {$pathDefinition}", $handler);
    }
    public static function options(string $pathDefinition, callable $handler): static
    {
        return new static("OPTIONS {$pathDefinition}", $handler);
    }
    public static function trace(string $pathDefinition, callable $handler): static
    {
        return new static("TRACE {$pathDefinition}", $handler);
    }
    public static function any(string $pathDefinition, callable $handler): static
    {
        return new static("GET|HEAD|POST|PUT|DELETE|CONNECT|OPTIONS|TRACE {$pathDefinition}", $handler);
    }

    protected function unpackPath(string $path): array
    {
        $regexpPieces = ['^'];
        $methods = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE'];
        $methodDefinitionPattern = implode(separator: '|', array: $methods) . ')(\|(' . implode(separator: '|', array: $methods) . ')){0,' . (count(value: $methods) - 1) . '}';
        $parameterNameDefinitionPattern = '(?<parameterNameDefinition>[a-zA-Z_][a-zA-Z0-9_]*?)';
        $parameterTypeDefinitionPattern = "(?<parameterTypeDefinition>(any|bool|float|int|regexp|string|uuid|uuid4))";
        $parameterTypeOptionsDefinitionPattern = '\((?<parameterTypeOptionsDefinition>.*?)\)';
        $parameterDefinitionPattern = "(?<parameterDefinition>\{{$parameterNameDefinitionPattern}(\s*\:\s*{$parameterTypeDefinitionPattern}({$parameterTypeOptionsDefinitionPattern})?)?\})";
        $prefixedLiteralPathDefinitionPattern = '(?<prefixedLiteralPathDefinition>[^\{\}\/\.]*?)';
        $literalPathDefinitionPattern = '(?<literalPathDefinition>\/[^\{\}\/\.]*?)';
        $extensions = $this->getAvailableFormatExtensions();
        $extensionDefinitionPattern = implode(separator: '|', array: array_map(callback: function ($extension): string {
            return preg_quote(str: $extension, delimiter: '/');
        }, array: $extensions));
        $pathDefinitionPattern = "({$literalPathDefinitionPattern}|{$parameterDefinitionPattern}|{$prefixedLiteralPathDefinitionPattern})+";
        $definitionPattern = "^\s*((?i)(?<methodDefinition>({$methodDefinitionPattern})\s+)?(?<pathDefinition>({$pathDefinitionPattern}))(?<extensionDefinition>{$extensionDefinitionPattern})?\s*$";
        if (!Preg::isMatch(pattern: "/{$definitionPattern}/Anu", subject: $path, matches: $matches)) {
            throw new InvalidArgumentException(message: 'path parsing failed');
        }
        $regexpPieces[] = match (empty($matches['methodDefinition'])) {
            false => "({$matches['methodDefinition']})\s+",
            default => 'GET\s+'
        };
        $quoteLiterals = function (string $string) use ($literalPathDefinitionPattern): string {
            return Preg::replaceCallback(
                pattern: "/{$literalPathDefinitionPattern}/nu",
                replacement: function ($match): string {
                    return preg_quote(str: $match['literalPathDefinition'], delimiter: '/');
                },
                subject: $string,
            );
        };
        $regexpPieces[] = match (Preg::isMatchAll(pattern: "/{$parameterDefinitionPattern}/nu", subject: $matches['pathDefinition'], matches: $parameterMatches)) {
            true => Preg::replaceCallback(
                pattern: "/{$parameterDefinitionPattern}/nu",
                replacement: function ($match): string {
                        $pattern = match ($match['parameterTypeDefinition']) {
                            'any' => '.+?',
                            'bool' => '[true|false]',
                            'float' => '[\-\+]?[0-9]+(\.[0-9]+?)?',
                            'int' => '[\-\+]?[0-9]+?',
                            'regexp' => match (empty($match['parameterTypeOptionsDefinition'])) {
                                    true => throw new InvalidArgumentException(message: 'regexp type requires pattern option in parentheses, i.e param_name:regexp([a-z0-9]+?)'),
                                    default => $match['parameterTypeOptionsDefinition']
                                },
                            'uuid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
                            'uuid4' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}',
                            default => '[^\/]+?' // same as string
                        };
                        return "(?<{$match['parameterNameDefinition']}>{$pattern})";
                    },
                subject: $quoteLiterals(string: $matches['pathDefinition']),
            ),
            default => $quoteLiterals(string: $matches['pathDefinition'])
        };
        $regexpPieces[] = '$';
        return [
            implode(separator: '', array: $regexpPieces),
            $matches['extensionDefinition'] ?? null
        ];
    }

    protected function checkIfFormatIsSupported(string $format): bool
    {
        return in_array(needle: $format, haystack: array_keys(Config::RESPONSE_EMITTERS));
    }

    public function setTemplate(string|callable $template, ?string $format = Config::DEFAULT_RESPONSE_FORMAT): static
    {
        if (!$this->checkIfFormatIsSupported(format: $format)) {
            throw new ValueError(message: "format {$format} is not supported");
        }
        $this->templates[$format] = match(is_callable($template)) {
            true => $template($format),
            // TODO: check template path validity with realpath
            default => $template,
        };
        return $this;
    }

    public function getTemplate(string $format = Config::DEFAULT_RESPONSE_FORMAT): ?string
    {
        if (!$this->checkIfFormatIsSupported(format: $format)) {
            throw new ValueError(message: "format {$format} is not supported");
        }
        return $this->templates[$format] ?? null;
    }
    public function setHandler(callable $handler): static
    {
        $this->handler = match($handler instanceof Closure) {
            true => $handler,
            default => $handler(...)
        };
        return $this;
    }
    public function setName($name): static
    {
        $this->name = $name;
        return $this;
    }
    public function getName(): string|null
    {
        return $this->name;
    }
    public function getHandler(): Closure
    {
        return $this->handler;
    }
    public function getRequestPathRegExp(): string
    {
        return $this->requestPathRegExp;
    }
    public function getRequestParameters(RequestInterface $request, ?string $pathPrefix = null): array
    {
        $pathArguments = $this->extractPathArgumentsFromRequest(request: $request, pathPrefix: $pathPrefix) ?? [];
        $queryArguments = $request->getQueryParams() ?? [];
        $bodyArguments = $request->getParsedBody() ?? [];
        return [...$bodyArguments, ...$queryArguments, ...$pathArguments];
    }
    public function checkMatch(RequestInterface $request, ResponseInterface $response, ?string $pathPrefix = null): bool
    {
        $requestMethod = $request->getMethod();
        $requestPath = $request->getPath();
        $responseFormat = $response->getResponseFormat();
        $requestPath = match ($pathPrefix) {
            null => $requestPath,
            default => match(Preg::isMatch(pattern: sprintf('/^%s/', preg_quote(str: $pathPrefix, delimiter: '/')), subject: $requestPath)) {
                true => Preg::replace(pattern: sprintf('/^%s/', preg_quote(str: $pathPrefix, delimiter: '/')), replacement: '', subject: $requestPath),
                default => null
            }
        };
        return match($requestPath) {
            null => false,
            default => Preg::isMatch(pattern: "/{$this->requestPathRegExp}/", subject: "{$requestMethod} {$requestPath}")
                       &&
                       Preg::isMatch(pattern: "/^{$this->responseFormatRegExp}$/", subject: $responseFormat)
        };
    }
    public function invoke(RequestInterface &$request, ResponseInterface &$response, ?string $pathPrefix = null): mixed
    {
        $handler = $this->getHandler();
        $validator = new Validator(flags: (Config::VALIDATOR_ATTRIBUTES_MUST_BE_CLASSES ? Validator::ALL_ATTRIBUTES_MUST_BE_CLASSES : 0));
        $parameters = $this->getRequestParameters($request, $pathPrefix);
        $arguments = $validator->getFilteredArguments(handler: $handler, arguments: $parameters);
        $request->setParameters($parameters);
        $request->setValidatedParameters($arguments);
        try {
            if (isset($this->requestMiddleware)) {
                HookManager::triggerHook(hookName: HookManager::BEFORE_ROUTE_REQUEST_MIDDLEWARE, context: ['request' => $request, 'route' => $this, 'middleware' => $this->requestMiddleware]);
                $request = match (($requestMiddlewareResult = $this->requestMiddleware->invoke(request: $request, response: $response, context: $this)) instanceof RequestInterface) {
                    true => $requestMiddlewareResult,
                    default => $request
                };
                HookManager::triggerHook(hookName: HookManager::AFTER_ROUTE_REQUEST_MIDDLEWARE, context: ['request' => $request, 'route' => $this, 'middleware' => $this->requestMiddleware]);
            }
            HookManager::triggerHook(hookName: HookManager::BEFORE_ROUTE, context: ['route' => $this, 'handler' => $handler, 'parameters' => $arguments]);
            $handlerReflection = new ReflectionFunction($handler);
            $handlerThis = $handlerReflection->getClosureThis();
            $response->setData($result = match($handlerThis) {
                null => $handler(...$arguments),
                default => $handler->call($handlerThis, ...$arguments)
            });
            HookManager::triggerHook(hookName: HookManager::AFTER_ROUTE, context: ['route' => $this, 'result' => $result]);
            if (isset($this->responseMiddleware)) {
                HookManager::triggerHook(hookName: HookManager::BEFORE_ROUTE_RESPONSE_MIDDLEWARE, context: ['response' => $response, 'route' => $this, 'middleware' => $this->responseMiddleware]);
                $response = match (($responseMiddlewareResult = $this->responseMiddleware->invoke(response: $response, request: $request, context: $this)) instanceof ResponseInterface) {
                    true => $responseMiddlewareResult,
                    default => $response
                };
                HookManager::triggerHook(hookName: HookManager::AFTER_ROUTE_RESPONSE_MIDDLEWARE, context: ['response' => $response, 'route' => $this, 'middleware' => $this->responseMiddleware]);
            }
        } catch (Throwable $exception) {
            foreach ($this->exceptionHandlers as $className => $handler) {
                if (!$className || ($exception instanceof $className)) {
                    $handlerReflection = new ReflectionFunction($handler);
                    $handlerThis = $handlerReflection->getClosureThis();
                    if (($result = $handler->call($handlerThis, $exception, $request, $response, $this)) instanceof ResponseInterface) {
                        $response = $result;
                        return null;
                    }
                    $response->setData($result);
                    return $result;
                }
            }
            throw $exception;
        }
        return $result;
    }
    protected function extractPathArgumentsFromRequest(RequestInterface $request, ?string $pathPrefix): array
    {
        $requestMethod = $request->getMethod();
        $requestPath = $request->getPath();
        $requestPath = match ($pathPrefix) {
            null => $requestPath,
            default => Preg::replace(pattern: sprintf('/^%s/', preg_quote(str: $pathPrefix, delimiter: '/')), replacement: '', subject: $requestPath)
        };
        $subject = "{$requestMethod} {$requestPath}";
        if (!Preg::isMatch(pattern: "/{$this->requestPathRegExp}/", subject: $subject, matches: $matches)) {
            return [];
        }
        array_walk(array: $matches, callback: function ($value, $key) use (&$matches): void {
            if (is_numeric(value: $key)) {
                unset($matches[$key]);
            }
        });
        return $matches;
    }
}
