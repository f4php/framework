<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Tests\Core\MockRequest;

final class RequestTest extends TestCase
{
    public function testPathMatching(): void
    {
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/test1/test2');
        $this->assertSame(true, $request->checkIfPathMatches('/^\/test1\/test2$/'));
        $this->assertSame(false, $request->checkIfPathMatches('/^non-matching$/'));
    }

}