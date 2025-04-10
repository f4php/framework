<?php

declare(strict_types=1);

namespace F4\Core;

use Composer\InstalledVersions;

use F4\Config;
use F4\HookManager;
use F4\Loader;
use F4\Profiler;
use F4\Core\DebuggerInterface;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\MiddlewareInterface;

use F4\Core\Debugger\ExportResult;
use F4\Core\Debugger\BacktraceResult;

use F4\Core\Phug\TemplateRenderer as PhugTemplateRenderer;

use Closure;
use ErrorException;
use ReflectionFunction;

use function array_map;
use function array_slice;
use function debug_backtrace;
use function file_get_contents;
use function header;
use function headers_list;
use function header_remove;
use function mb_split;
use function ob_get_clean;
use function ob_start;
use function php_sapi_name;
use function php_uname;
use function phpversion;
use function sprintf;

class Debugger implements DebuggerInterface
{
    protected RequestInterface $request;
    protected ResponseInterface $response;
    protected ?MiddlewareInterface $requestMiddleware = null;
    protected ?MiddlewareInterface $routeGroupRequestMiddleware = null;
    protected ?MiddlewareInterface $routeRequestMiddleware = null;
    protected ?MiddlewareInterface $routeResponseMiddleware = null;
    protected ?MiddlewareInterface $routeGroupResponseMiddleware = null;
    protected ?MiddlewareInterface $responseMiddleware = null;
    protected ?RouteGroupInterface $routeGroup = null;
    protected ?RouteInterface $route = null;
    protected array $routeParameters = [];
    protected array $logEntries = [];
    protected array $queries = [];
    protected mixed $templateContext = null;
    public function __construct()
    {

        Profiler::init();

        HookManager::addHook(HookManager::AFTER_CORE_CONSTRUCT, function ($context) {
            Profiler::addSnapshot('Core ready');
        });
        HookManager::addHook(HookManager::AFTER_SETUP_REQUEST_RESPONSE, function ($context) {
            Profiler::addSnapshot('Setup request/response');
        });
        HookManager::addHook(HookManager::AFTER_SETUP_ENVIRONMENT, function ($context) {
            Profiler::addSnapshot('Setup environment');
        });
        HookManager::addHook(HookManager::AFTER_SETUP_EMITTER, function ($context) {
            Profiler::addSnapshot('Setup emitter');
        });
        HookManager::addHook(HookManager::AFTER_REGISTER_MODULES, function ($context) {
            Profiler::addSnapshot('Register modules');
        });
        HookManager::addHook(HookManager::BEFORE_ROUTING, function ($context) {
            Profiler::addSnapshot('Route matching');
        });
        HookManager::addHook(HookManager::BEFORE_REQUEST_MIDDLEWARE, function ($context) {
            $this->requestMiddleware = $context['middleware'];
        });
        HookManager::addHook(HookManager::BEFORE_ROUTE_GROUP_REQUEST_MIDDLEWARE, function ($context) {
            $this->routeGroupRequestMiddleware = $context['middleware'];
        });
        HookManager::addHook(HookManager::BEFORE_ROUTE_REQUEST_MIDDLEWARE, function ($context) {
            $this->routeRequestMiddleware = $context['middleware'];
        });
        HookManager::addHook(HookManager::BEFORE_ROUTE_GROUP, function ($context) {
            $this->routeGroup = $context['routeGroup'];
        });
        HookManager::addHook(HookManager::BEFORE_ROUTE, function ($context) {
            $this->route = $context['route'];
            $this->routeParameters = $context['parameters'];
        });
        HookManager::addHook(HookManager::BEFORE_ROUTE_RESPONSE_MIDDLEWARE, function ($context) {
            $this->routeResponseMiddleware = $context['middleware'];
        });
        HookManager::addHook(HookManager::BEFORE_ROUTE_GROUP_RESPONSE_MIDDLEWARE, function ($context) {
            $this->routeGroupResponseMiddleware = $context['middleware'];
        });
        HookManager::addHook(HookManager::BEFORE_RESPONSE_MIDDLEWARE, function ($context) {
            $this->responseMiddleware = $context['middleware'];
        });
        HookManager::addHook(HookManager::AFTER_PROCESS_REQUEST, function ($context) {
            Profiler::addSnapshot('Prepare response');
        });
        HookManager::addHook(HookManager::AFTER_TEMPLATE_CONTEXT_READY, function ($context) {
            $this->templateContext = $context['context'];
        });
        HookManager::addHook(HookManager::BEFORE_EMIT_RESPONSE, function ($context) {
            $this->request = $context['f4']->getRequest();
            $this->response = $context['f4']->getResponse();
        });
        HookManager::addHook(HookManager::AFTER_EMIT_RESPONSE, function ($context) {
            Profiler::addSnapshot('Render response');
        });
        // HookManager::addHook(HookManager::AFTER_ROUTE, function($context) {

        // });
        if (Config::DEBUG_DB_QUERIES) {
            HookManager::addHook(
                HookManager::BEFORE_SQL_SUBMIT,
                function ($context) {
                    Profiler::addSnapshot(); // makes sql profiling more accurate
                }
            );
            HookManager::addHook(HookManager::AFTER_SQL_SUBMIT, function ($context) {
                $this->queries[count($this->queries) + 1] = $context['statement'];
                Profiler::addSnapshot('Execute query ' . count($this->queries), $context['statement'] ?? null);
            });
        }
    }
    public function log(mixed $value, ?string $description = null): void
    {
        $this->logEntries[] = [
            'value' => $value,
            'description' => $description,
            'trace' => match ($caller = debug_backtrace()[3] ?? null) {
                null => [],
                default => [
                    $caller,
                ]
            }
        ];
    }
    public function checkIfEnabledByRequest(RequestInterface $request): bool
    {
        return $request->getDebugExtension() !== null;
    }
    public function captureAndEmit(callable $emitCallback): bool
    {
        ob_start();
        $emitCallback();
        $headers = [];
        foreach (headers_list() as $value) {
            [$name, $value] = mb_split(':\s*', $value, 2);
            $headers[$name] = [$value];
        }
        header_remove(null);
        $output = ob_get_clean();

        if (php_sapi_name() === 'cli') {
            throw new ErrorException('CLI mode debugger is not yet implemented');
        }
        $data = [
            'route' => [
                ...($this->route ? self::exportClosure($this->route->getHandler()) : []),
                'parameters' => ExportResult::fromVariable($this->routeParameters)->toArray()['value'] ?? [],
                'requestMiddleware' => $this->requestMiddleware ? self::exportClosure($this->requestMiddleware->getHandler()) : null,
                'responseMiddleware' => $this->responseMiddleware ? self::exportClosure($this->responseMiddleware->getHandler()) : null,
                'routeGroupRequestMiddleware' => $this->routeGroupRequestMiddleware ? self::exportClosure($this->routeGroupRequestMiddleware->getHandler()) : null,
                'routeGroupResponseMiddleware' => $this->routeGroupResponseMiddleware ? self::exportClosure($this->routeGroupResponseMiddleware->getHandler()) : null,
                'routeRequestMiddleware' => $this->routeRequestMiddleware ? self::exportClosure($this->routeRequestMiddleware->getHandler()) : null,
                'routeResponseMiddleware' => $this->routeResponseMiddleware ? self::exportClosure($this->routeResponseMiddleware->getHandler()) : null,
            ],
            'request' => [
                'body' => $this->request->getBody()->getContents(),
                'debugExtension' => $this->request->getDebugExtension(),
                'extension' => $this->request->getExtension(),
                'headers' => $this->request->getHeaders(),
                'method' => $this->request->getMethod(),
                'path' => $this->request->getPath(),
                'parameters' => ExportResult::fromVariable($this->request->getParameters())->toArray()['value'] ?? []
            ],
            'response' => [
                'body' => $output,
                'code' => $this->response->getStatusCode(),
                'data' => ExportResult::fromVariable($this->response->getData())->toArray()['value'] ?? null,
                'exception' => match ($exception = $this->response->getException()) {
                    null => null,
                    default => [
                        'code' => $exception->getCode(),
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => BacktraceResult::fromThrowable($exception)->toArray(),
                    ]
                },
                'format' => $this->response->getResponseFormat(),
                'headers' => $headers,
                'meta' => ExportResult::fromVariable($this->response->getMetaData())->toArray()['value'] ?? null,
                'status' => $this->response->getReasonPhrase(),
                'template' => match ($template = $this->response->getTemplate()) {
                    null => null,
                    default => [
                        'path' => $template,
                        'context' => ExportResult::fromVariable($this->templateContext)->toArray()['value'] ?? null,
                        'folders' => array_map(function ($path) {
                                return realpath($path);
                            }, Config::TEMPLATE_PATHS),
                        // 'body' => '',
                    ]
                },
            ],
            'queries' => ExportResult::fromVariable($this->queries)->toArray(),
            // 'hooks' => ExportResult::fromVariable(HookManager::getHooks())->toArray(),
            'profiler' => Profiler::getSnapshots(),
            'config' => ExportResult::fromVariable(new Config())->toArray(),
            'log' => array_map(function ($entry) {
                return [
                    'description' => $entry['description'],
                    'trace' => $entry['trace'],
                    'value' => ExportResult::fromVariable($entry['value'])->toArray()
                ];
            }, $this->logEntries),
            'session' => ExportResult::fromVariable($_SESSION ?? [])->toArray(),
            'project' => [
                'root' => realpath(Loader::getPath()),
            ],
            'system' => ExportResult::fromVariable([
                'F4 environment' => Loader::getCurrentEnvironment(),
                'F4 version' => InstalledVersions::getPrettyVersion('f4php/framework'),
                'Project Root' => realpath(Loader::getPath()),
                'PHP version' => phpversion(),
                'PHP extensions' => get_loaded_extensions(),
                'OS info' => php_uname(),
            ])->toArray(),
        ];
        header(sprintf(
            '%s: %s',
            'Content-Type',
            "text/html; charset=" . Config::RESPONSE_CHARSET,
        ), true, 200);
        $pugRenderer = new PhugTemplateRenderer();
        $pugRenderer->displayFile(__DIR__ . '/../../../templates/debugger/debugger.pug', $data);
        return true;
    }

    protected static function exportClosure(Closure $closure): array
    {
        $closureReflection = new ReflectionFunction($closure);
        $code = implode(
            "\n",
            array_slice(
                mb_split('\r?\n', file_get_contents($closureReflection->getFileName())),
                $closureReflection->getStartLine() - 1,
                $closureReflection->getEndLine() - $closureReflection->getStartLine() + 1
            ),
        );
        return [
            'name' => $closureReflection->name,
            'file' => $closureReflection->getFileName(),
            'line' => $closureReflection->getStartLine(),
            'code' => $code
        ];
    }

}
