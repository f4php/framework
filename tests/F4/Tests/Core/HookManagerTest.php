<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Core\HookManager;

final class HookManagerTest extends TestCase
{
    public function testHook(): void
    {
        HookManager::addHook(hookName: HookManager::BEFORE_PROCESS_REQUEST, callback: function(mixed $context): string {
            return 'test-hook-result:'.$context['test'];
        });
        $this->assertSame('test-hook-result:test-value', HookManager::triggerHook(hookName: HookManager::BEFORE_PROCESS_REQUEST, context: ['test'=>'test-value'])[0]);
    }

}