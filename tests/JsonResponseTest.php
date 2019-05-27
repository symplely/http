<?php

namespace Async\Tests;

use Async\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{
    public function testInstanceOf(): void
    {
        $fixture = new JsonResponse();

        self::assertInstanceOf(ResponseInterface::class, $fixture);
    }

    public function testDefaults(): void
    {
        $fixture = new JsonResponse();

        $expected = ['Content-Type' => ['application/json']];
        self::assertEquals('""', (string) $fixture->getBody());
        self::assertEquals(200, $fixture->getStatusCode());
        self::assertEquals($expected, $fixture->getHeaders());
    }

    public function testBodyIsJsonEncoded(): void
    {
        $data = [uniqid('a') => uniqid('a'), uniqid('b') => uniqid('b')];
        $json = json_encode($data);

        $fixture = new JsonResponse(200, $data);
        $actual  = $fixture->getBody();

        self::assertEquals($json, (string) $actual);
    }

    public function testStatusCode(): void
    {
        $statusCode = rand(1, 5) * 100 + rand(0, 3);

        $fixture = new JsonResponse($statusCode, '');
        $actual  = $fixture->getStatusCode();

        self::assertEquals($statusCode, $actual);
    }

    public function testThrowsException(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Failed to encode data as JSON.');

        new JsonResponse(200, NAN);
    }
}
