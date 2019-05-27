<?php

namespace Async\Tests;

use Async\Http\RedirectResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RedirectResponseTest extends TestCase
{
    public function testInstanceOf(): void
    {
        $fixture = new RedirectResponse(302, uniqid());

        self::assertInstanceOf(ResponseInterface::class, $fixture);
    }

    public function testDefaults(): void
    {
        $uri = uniqid();

        $fixture = new RedirectResponse(302, $uri);

        $expected = '<html><body><p>This page has been moved <a href="'
            .htmlspecialchars($uri, ENT_QUOTES, 'UTF-8')
            .'">here</a>.</p></body></html>';
        self::assertEquals($expected, (string) $fixture->getBody());
        self::assertEquals(302, $fixture->getStatusCode());
        self::assertEquals(['Location' => [$uri]], $fixture->getHeaders());
    }

    public function testStatusCode(): void
    {
        $statusCode = rand(1, 5) * 100 + rand(0, 3);

        $fixture = new RedirectResponse($statusCode, uniqid());
        $actual  = $fixture->getStatusCode();

        self::assertEquals($statusCode, $actual);
    }
}
