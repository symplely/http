<?php

namespace Async\Tests;

use Async\Http\Stream;
use Async\Http\UploadedFileFactory;
use PHPUnit\Framework\TestCase;

class UploadedFileFactoryTest extends TestCase
{
    /**
     * @var UploadedFileFactoryInterface
     */
    protected $factory;

    public function testUploadedFile()
    {
        $factory = new UploadedFileFactory();
        $streamFile = new Stream(__FILE__, 'r');
        $file = $factory->createUploadedFile($streamFile, $size = filesize(__FILE__), UPLOAD_ERR_OK, $name = basename(__FILE__), 'text/plain');
        $this->assertInstanceOf('Psr\\Http\\Message\\UploadedFileInterface', $file);
        $this->assertEquals($name, $file->getClientFilename());
        $this->assertEquals('text/plain', $file->getClientMediaType());
        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
        $this->assertEquals($size, $file->getSize());
        $this->assertInstanceOf('Psr\\Http\\Message\\StreamInterface', $file->getStream());
    }
}
