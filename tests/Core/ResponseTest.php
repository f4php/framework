<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Tests\Core\MockResponse;

final class ResponseTest extends TestCase
{
    public function testState(): void
    {
        $response = new MockResponse();
        $response->setState('test', 'test value 1');
        ;
        $this->assertSame('test value 1', $response->getState('test'));
    }

}