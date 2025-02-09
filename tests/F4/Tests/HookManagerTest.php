<?php

declare(strict_types=1);

namespace F4\Tests;
use PHPUnit\Framework\TestCase;

use F4\HookManager;
use F4\Tests\MockModule;
use F4\Tests\Core\MockCore;
use F4\Tests\Core\MockRequest;
use F4\Tests\Core\MockResponse;

use ErrorException;

final class HookManagerTest extends TestCase
{
    public function testHook(): void
    {
        HookManager::addHook(hookName: HookManager::BEFORE_PROCESS_REQUEST, callback: function(mixed $context): string {
            return 'test-hook-result:'.$context['test'];
        });
        $this->assertSame('test-hook-result:test-value', HookManager::triggerHook(hookName: HookManager::BEFORE_PROCESS_REQUEST, context: ['test'=>'test-value'])[0]);
    }
    public function testHookSequence(): void
    {
        $result = [];
        HookManager::resetHooks();
        HookManager::addHook(hookName: HookManager::AFTER_CORE_CONSTRUCT, callback: function(mixed $context) use (&$result): void {
            $result[] = 1;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_SETUP_REQUEST_RESPONSE, callback: function(mixed $context) use (&$result): void {
            $result[] = 2;
        });
        HookManager::addHook(hookName: HookManager::AFTER_SETUP_REQUEST_RESPONSE, callback: function(mixed $context) use (&$result): void {
            $result[] = 3;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_SETUP_ENVIRONMENT, callback: function(mixed $context) use (&$result): void {
            $result[] = 4;
        });
        HookManager::addHook(hookName: HookManager::AFTER_SETUP_ENVIRONMENT, callback: function(mixed $context) use (&$result): void {
            $result[] = 5;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_SETUP_EMITTER, callback: function(mixed $context) use (&$result): void {
            $result[] = 6;
        });
        HookManager::addHook(hookName: HookManager::AFTER_SETUP_EMITTER, callback: function(mixed $context) use (&$result): void {
            $result[] = 7;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_REGISTER_MODULES, callback: function(mixed $context) use (&$result): void {
            $result[] = 8;
        });
        HookManager::addHook(hookName: HookManager::AFTER_REGISTER_MODULES, callback: function(mixed $context) use (&$result): void {
            $result[] = 9;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_PROCESS_REQUEST, callback: function(mixed $context) use (&$result): void {
            $result[] = 10;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_REQUEST_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 11;
        });
        HookManager::addHook(hookName: HookManager::AFTER_REQUEST_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 12;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_ROUTING, callback: function(mixed $context) use (&$result): void {
            $result[] = 13;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_ROUTE_GROUP_REQUEST_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 14;
        });
        HookManager::addHook(hookName: HookManager::AFTER_ROUTE_GROUP_REQUEST_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 15;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_ROUTE_GROUP, callback: function(mixed $context) use (&$result): void {
            $result[] = 16;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_ROUTE_REQUEST_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 17;
        });
        HookManager::addHook(hookName: HookManager::AFTER_ROUTE_REQUEST_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 18;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_ROUTE, callback: function(mixed $context) use (&$result): void {
            $result[] = 19;
        });
        HookManager::addHook(hookName: HookManager::AFTER_ROUTE, callback: function(mixed $context) use (&$result): void {
            $result[] = 20;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_ROUTE_RESPONSE_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 21;
        });
        HookManager::addHook(hookName: HookManager::AFTER_ROUTE_RESPONSE_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 22;
        });
        HookManager::addHook(hookName: HookManager::AFTER_ROUTE_GROUP, callback: function(mixed $context) use (&$result): void {
            $result[] = 23;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_ROUTE_GROUP_RESPONSE_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 24;
        });
        HookManager::addHook(hookName: HookManager::AFTER_ROUTE_GROUP_RESPONSE_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 25;
        });
        HookManager::addHook(hookName: HookManager::AFTER_ROUTING, callback: function(mixed $context) use (&$result): void {
            $result[] = 26;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_RESPONSE_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 27;
        });
        HookManager::addHook(hookName: HookManager::AFTER_RESPONSE_MIDDLEWARE, callback: function(mixed $context) use (&$result): void {
            $result[] = 28;
        });
        HookManager::addHook(hookName: HookManager::AFTER_PROCESS_REQUEST, callback: function(mixed $context) use (&$result): void {
            $result[] = 29;
        });
        HookManager::addHook(hookName: HookManager::BEFORE_EMIT_RESPONSE, callback: function(mixed $context) use (&$result): void {
            $result[] = 30;
        });
        HookManager::addHook(hookName: HookManager::AFTER_EMIT_RESPONSE, callback: function(mixed $context) use (&$result): void {
            $result[] = 31;
        });

        $mockCore = new MockCore();
        $mockRequest = new MockRequest('GET', '/test');
        $mockResponse = new MockResponse('text/html');

        $mockCore
            ->setUpRequestResponse(function() use ($mockRequest, $mockResponse) {
                /**
                 * @var MockCore $this
                 * @suppresswarnings PHP1416
                 * @phpstan-ignore method.protected
                 */
                $this->setUpRequestResponseNormally($mockRequest, $mockResponse);
            })
            ->setUpEnvironment(function() {
                /**
                 * @var MockCore $this
                 * @suppresswarnings PHP1416
                 * @phpstan-ignore method.protected
                 */
                $this->setUpEnvironmentNormally(false, true);
            })
            ->setUpEmitter()
            ->registerModules(function() {
                /**
                 * @var MockCore $this
                 * @suppresswarnings PHP1416
                 * @phpstan-ignore method.protected
                 */
                $this->registerModulesNormally([MockModule::class]);
            })
            ->processRequest()
            ->emitResponse(function() {
                /**
                 * @var MockCore $this
                 * @suppresswarnings PHP1416
                 */
                if(!@$this->emit(response: $this->getResponse(), request: $this->getRequest())) { // @ prevents Phug deprecation warnings
                    throw new ErrorException(message: 'Failed to emit response');
                }
            })
            ->restoreEnvironment(function() {
                /**
                 * @var MockCore $this
                 * @suppresswarnings PHP1416
                 * @phpstan-ignore method.protected
                 */
                $this->restoreEnvironmentNormally(false, true);
            });

        $this->assertSame(1, $result[0]);
        $this->assertSame(2, $result[1]);
        $this->assertSame(3, $result[2]);
        $this->assertSame(4, $result[3]);
        $this->assertSame(5, $result[4]);
        $this->assertSame(6, $result[5]);
        $this->assertSame(7, $result[6]);
        $this->assertSame(8, $result[7]);
        $this->assertSame(9, $result[8]);
        $this->assertSame(10, $result[9]);
        $this->assertSame(11, $result[10]);
        $this->assertSame(12, $result[11]);
        $this->assertSame(13, $result[12]);
        $this->assertSame(14, $result[13]);
        $this->assertSame(15, $result[14]);
        $this->assertSame(16, $result[15]);
        $this->assertSame(17, $result[16]);
        $this->assertSame(18, $result[17]);
        $this->assertSame(19, $result[18]);
        $this->assertSame(20, $result[19]);
        $this->assertSame(21, $result[20]);
        $this->assertSame(22, $result[21]);
        $this->assertSame(23, $result[22]);
        $this->assertSame(24, $result[23]);
        $this->assertSame(25, $result[24]);
        $this->assertSame(26, $result[25]);
        $this->assertSame(27, $result[26]);
        $this->assertSame(28, $result[27]);
        $this->assertSame(29, $result[28]);
        $this->assertSame(30, $result[29]);
        $this->assertSame(31, $result[30]);
    }

}