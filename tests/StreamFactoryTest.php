<?php

namespace Async\Tests;

use Async\Http\StreamFactory;
use Psr\Http\Factory\StreamFactoryInterface;
use PHPUnit\Framework\TestCase;

class StreamFactoryTest extends TestCase
{
    /**
     * @var StreamFactoryInterface
     */
    protected $factory;

    public function setUp(): void
    {
        $this->factory = new StreamFactory();
    }

    public function testStream()
    {
        $this->assertInstanceOf(
            'Psr\\Http\\Message\\StreamInterface',
            $stream = $this->factory->createStream()
        );
        $this->assertEmpty($stream->getContents());
        $stream->write($text = 'Hello');
        $this->assertEquals($text, (string)$stream);
    }

    public function testStreamFromString()
    {
        $this->assertInstanceOf(
            'Psr\\Http\\Message\\StreamInterface',
            $stream = $this->factory->createStream($text = 'Hello')
        );
        $this->assertEquals($text, (string)$stream);
    }

    public function testStreamFromFile()
    {
        $this->assertInstanceOf(
            'Psr\\Http\\Message\\StreamInterface',
            $stream = $this->factory->createStreamFromFile(__FILE__, 'r')
        );
        $this->assertNotEmpty($text = $stream->read(5));
        $this->assertEquals('<?php', $text);
        $stream->close();
    }

    public function testStreamFromResource()
    {
        $handle = fopen(__FILE__, 'r');
        $this->assertInstanceOf(
            'Psr\\Http\\Message\\StreamInterface',
            $stream = $this->factory->createStreamFromResource($handle)
        );
        $this->assertNotEmpty($text = $stream->read(5));
        $this->assertEquals('<?php', $text);
        $stream->close();
    }
}
