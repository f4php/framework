<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Config;
use F4\Core\DebuggerInterface;
use F4\Core\HookManager;
use F4\Core\Profiler;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;

use F4\Core\Phug\TemplateRenderer as PhugTemplateRenderer;

use Nyholm\Psr7\Factory\Psr17Factory;

use ErrorException;

use function debug_backtrace;
use function header;

class Debugger implements DebuggerInterface
{

    protected RequestInterface $request;
    protected ResponseInterface $response;
    protected $backtrace = [];

    public function __construct() {

        Profiler::init();

        HookManager::addHook(HookManager::AFTER_CORE_CONSTRUCT, function($context) {
            Profiler::addSnapshot('Core ready');
        });
        HookManager::addHook(HookManager::AFTER_SETUP_REQUEST_RESPONSE, function($context) {
            Profiler::addSnapshot('Setup request/response');
        });
        HookManager::addHook(HookManager::AFTER_SETUP_ENVIRONMENT, function($context) {
            Profiler::addSnapshot('Setup environment');
        });
        HookManager::addHook(HookManager::AFTER_SETUP_EMITTER, function($context) {
            Profiler::addSnapshot('Setup emitter');
        });
        HookManager::addHook(HookManager::AFTER_REGISTER_MODULES, function($context) {
            Profiler::addSnapshot('Register modules');
        });
        HookManager::addHook(HookManager::AFTER_PROCESS_REQUEST, function($context) {
            Profiler::addSnapshot('Process request');
        });
        HookManager::addHook(HookManager::BEFORE_EMIT_RESPONSE, function($context) {
            $this->request = $context['f4']->getRequest();
            $this->response = $context['f4']->getResponse();
        });
        HookManager::addHook(HookManager::AFTER_EMIT_RESPONSE, function($context) {
            Profiler::addSnapshot('Render response');
        });
        HookManager::addHook(HookManager::AFTER_ROUTE, function($context) {
            $this->backtrace = debug_backtrace();
        });
    }
    public function checkIfEnabledByRequest(RequestInterface $request): bool {
        return $request->getDebugExtension() !== null;
    }
    public function captureAndEmit(callable $emitCallback): bool
    {
        ob_start();
        $emitCallback();
        $output = ob_get_clean();

        if (php_sapi_name() === 'cli') {
            throw new ErrorException('CLI mode debugger is not yet implemented');
        }
        $_SESSION['test']=[
            'a'=>'b',
            'b'=>'c'
        ];
        $_SESSION['complex']=[
            'a'=>
                ['b'=>'c']
        ];
        $data = [
            'session'=>isset($_SESSION)?$_SESSION:[],
            'request'=>[
                'headers' => $this->request->getHeaders(),
                'body' => $this->request->getParsedBody(),
            ],//$this->collectedData['request']->asArray(),
            'response'=>[
                'headers' => $this->response->getHeaders(),
                'data' => $this->response->getData(),
                'meta' => $this->response->getMetaData(),
                'exception' => $this->response->getException(),
                'template' => [
                    match($template = $this->response->getTemplate()) {
                        null => [],
                        default => [
                            'name' => $template,
                            'body' => '',//file_get_contents(__DIR__."/../../../templates/{$template}")
                        ]
                    }
                ],
                'body' => $output
            ],//$this->collectedData['response']->withParsedBody($output)->asArray(),
            'hooks'=>HookManager::getHooks(),
            'profiler'=>Profiler::getSnapshots(),
            'trace'=>$this->backtrace,
            // 'route'=>$this->f4->getRouter()->getMatchingRoute($request, $response)
        ];
        header(sprintf(
            '%s: %s',
            'Content-Type',
            "text/html; charset=" . Config::RESPONSE_CHARSET,
        ), true, 200);
        $pugRenderer = new PhugTemplateRenderer();
        $pugRenderer->displayFile(__DIR__ . '/../../../templates/debugger/index.pug', $data);
        return true;
    }
}
