<?php

namespace Async\Tests;

use Async\Http\Stream;
use Async\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testBody()
    {
        $response = new Response(200, $body = new Stream());
        $this->assertSame($body, $response->getBody());
    }

    public function testStatus()
    {
        $response = new Response();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            401,
            $response->withStatus(401)
                ->getStatusCode()
        );
        $this->assertEmpty(
            $response->withStatus(401)
                ->getReasonPhrase()
        );
        $this->assertEquals(
            'Unauthorized',
            $response->withStatus(401, 'Unauthorized')
                ->getReasonPhrase()
        );
    }

    public function testStatusInvalid()
    {
        $response = new Response();
        $this->expectException(\InvalidArgumentException::class);
        $response->withStatus(600);
    }
}
