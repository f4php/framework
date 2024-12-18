<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Core\Exception\HttpException;
use F4\Tests\Core\MockResponse;

final class ResponseTest extends TestCase
{
    public function testAsArray(): void {
        $response = (new MockResponse('text/html'))
            ->withHeader('X-Test', 'test-value')
            ->setData(['test'=>'value']);
        $response
            ->addMetaData(['meta-test'=>'meta-value'])
            ;
        $response
            ->setException(new HttpException('test-exception', 400))
            ;
        $this->assertSame('test-value', $response->asArray()['headers']['X-Test'][0]);
        $this->assertSame('value', $response->asArray()['data']['test']);
        $this->assertSame('meta-value', $response->asArray()['meta'][0]['meta-test']);
        $this->assertSame('test-exception', $response->asArray()['exception']->asArray()['message']);
        $this->assertSame('text/html', $response->asArray()['responseFormat']);
    }

}