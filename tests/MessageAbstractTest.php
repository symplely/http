<?php

namespace Async\Http;

use Async\Http\MessageAbstract;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageAbstractTest
 * @package Async\Http
 */
class MessageAbstractTest extends TestCase
{
    /**
     * @var MessageAbstract
     */
    private $fixture = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = $this->getMockForAbstractClass(MessageAbstract::class);
    }

    public function testGetHeaderLineWhenPresent(): void
    {
        $header = uniqid('header');
        $valueA = uniqid('value');
        $valueB = uniqid('value');
        $clone  = $this->fixture->withHeader($header, [$valueA, $valueB]);
        $actual = $clone->getHeaderLine(strtoupper($header));
        self::assertEquals($valueA.','.$valueB, $actual);
    }

    public function testWithBody(): void
    {
        $body = $this->createConfiguredMock(StreamInterface::class, ['getContents' => uniqid()]);
        $clone = $this->fixture->withBody($body);
        $old   = $this->fixture->getBody();
        $new   = $clone->getBody();
        self::assertNotSame($this->fixture, $clone);
        self::assertEquals('', $old->getContents());
        self::assertNotSame($body, $new);
        self::assertSame($body->getContents(), $new->getContents());
    }

    public function testHeaders()
    {
        $message = $this->getMockForAbstractClass(\Async\Http\MessageAbstract::class);
        $message = $message->withHeader('Content-Length', '128');
        $this->assertTrue(is_array($message->getHeaders()));
        $this->assertCount(1, $message->getHeaders());
        $this->assertTrue($message->hasHeader('Content-Length'));
        $this->assertTrue(is_array($message->getHeader('Content-Length')));
        $this->assertEquals('128', $message->getHeaderLine('Content-Length'));
        $this->assertFalse($message->hasHeader('Content-Type'));
        $this->assertEmpty($message->getHeaderLine('Content-Type'));
        $this->assertTrue(
            $message->withHeader('Content-Type', 'text/plain')
                ->hasHeader('Content-Type')
        );
        $this->assertFalse(
            $message->withHeader('Content-Type', 'text/plain')
                ->withoutHeader('Content-Type')
                ->hasHeader('Content-Type')
        );
        $this->assertEquals(
            array('text/plain'),
            $message->withHeader('Content-Type', 'text/plain')
                ->getHeader('Content-Type')
        );
        $this->assertEquals(
            array('text/plain'),
            $message->withAddedHeader('Content-Type', 'text/plain')
                ->getHeader('Content-Type')
        );
        $this->assertEquals(
            array('text/plain', 'text/html'),
            $message->withHeader('Content-Type', 'text/plain')
                ->withAddedHeader('Content-Type', 'text/html')
                ->getHeader('Content-Type')
        );
        $this->assertEquals(
            'text/plain,text/html',
            $message->withHeader('Content-Type', 'text/plain')
                ->withAddedHeader('Content-Type', 'text/html')
                ->getHeaderLine('Content-Type')
        );
    }

    public function testHeadersCaseInsensitive()
    {
        $message = $this->getMockForAbstractClass(\Async\Http\MessageAbstract::class);
        $message = $message->withHeader('Content-Length', $length = '128')
            ->withHeader('Content-Type', $type = 'text/html; charset=utf-8');
        $this->assertTrue($message->hasHeader('Content-Length'));
        $this->assertTrue($message->hasHeader('content-length'));
        $this->assertEquals($length, $message->getHeaderLine('Content-Length'));
        $this->assertEquals($length, $message->getHeaderLine('content-length'));
        $this->assertTrue($message->hasHeader('Content-Type'));
        $this->assertTrue($message->hasHeader('content-type'));
        $this->assertEquals($type, $message->getHeaderLine('Content-Type'));
        $this->assertEquals($type, $message->getHeaderLine('content-type'));
        $this->assertTrue(
            $message->withHeader('X-Powered-By', 'PHP/7.1')
                ->hasHeader('x-powered-by')
        );
        $this->assertTrue(
            $message->withHeader('x-powered-by', 'PHP/7.1')
                ->hasHeader('X-Powered-By')
        );
        $this->assertFalse(
            $message->withoutHeader('Content-Length')
                ->hasHeader('content-length')
        );
        $this->assertFalse(
            $message->withoutHeader('content-length')
                ->hasHeader('Content-Length')
        );
    }

    public function testHeaderInvalidName()
    {
        $message = $this->getMockForAbstractClass(\Async\Http\MessageAbstract::class);
        $this->expectException(\InvalidArgumentException::class);
        $message->withHeader('Some-Invalid<Name', 'Value');
    }

    public function testHeaderInvalidValue()
    {
        $message = $this->getMockForAbstractClass(\Async\Http\MessageAbstract::class);
        $this->expectException(\InvalidArgumentException::class);
        $message->withHeader('Some-Header', "Value\r\n");
    }

    public function testProtocolVersion()
    {
        $message = $this->getMockForAbstractClass(\Async\Http\MessageAbstract::class);
        $this->assertNotEmpty($message->getProtocolVersion());
        $this->assertEquals('1.1', $message->getProtocolVersion());
        $this->assertEquals(
            '1.0',
            $message->withProtocolVersion('1.0')
                ->getProtocolVersion()
        );
        $this->assertEquals(
            '1.1',
            $message->withProtocolVersion('1.1')
                ->getProtocolVersion()
        );
        $this->expectException(\InvalidArgumentException::class);
        $message->withProtocolVersion('10.0');
    }
}
