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

    public function testAsString(): void
    {
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/test1/test2');
        $this->assertSame('GET /test1/test2', $request->asString());
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/test1/test2', queryString: 'a=b&c=d&d[]=e');
        $this->assertSame('GET /test1/test2?a=b&c=d&d%5B0%5D=e', $request->asString());
    }

    public function testAsArray(): void
    {
        $request = new MockRequest(requestMethod: 'GET', requestPath: '/test1/test2', queryString: 'a=b&c=d&d[]=e');
        $this->assertSame('GET', $request->asArray()['method']);
        $this->assertSame('/test1/test2', $request->asArray()['path']);
        $this->assertSame('b', $request->asArray()['parameters']['a']);
        $this->assertSame('d', $request->asArray()['parameters']['c']);
        $this->assertSame('e', $request->asArray()['parameters']['d'][0]);
    }

}