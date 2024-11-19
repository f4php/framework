<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Tests\Core\MockRequest;

final class RequestTest extends TestCase
{
    public function testState(): void
    {
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/');
        $request->setState('test', 'test value 1');
        ;
        $this->assertSame('test value 1', $request->getState('test'));
    }

}