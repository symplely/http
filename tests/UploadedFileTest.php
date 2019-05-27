<?php

namespace Async\Tests;

use Async\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{
    public function testCreation()
    {
        $file = new UploadedFile('php://memory', 128, UPLOAD_ERR_OK, 'somefile.txt', 'text/plain');
        $this->assertEquals('somefile.txt', $file->getClientFilename());
        $this->assertEquals('text/plain', $file->getClientMediaType());
        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
        $this->assertEquals(128, $file->getSize());
        $this->assertInstanceOf('Psr\\Http\\Message\\StreamInterface', $file->getStream());
    }

    public function testCreationInvalidFile()
    {
        $this->expectException(\InvalidArgumentException::class);
        new UploadedFile(1, '128', UPLOAD_ERR_OK);
    }

    public function testCreationInvalidSize()
    {
        $this->expectException(\InvalidArgumentException::class);
        new UploadedFile('php://memory', '128', UPLOAD_ERR_OK);
    }

    public function testCreationInvalidError()
    {
        $this->expectException(\InvalidArgumentException::class);
        new UploadedFile('php://memory', '128', UPLOAD_ERR_EXTENSION + 1);
    }

    public function testMove()
    {
        $source = tempnam(sys_get_temp_dir(), 'urifile');
        $file = fopen($source, 'w');
        fputs($file, 'Something');
        fclose($file);
        $file = new UploadedFile($source, filesize($source), UPLOAD_ERR_OK, 'something.txt', 'text/plain');
        $file->moveTo(tempnam(sys_get_temp_dir(), 'urifile'));
        $this->expectException(\RuntimeException::class);
        $file->moveTo(tempnam(sys_get_temp_dir(), 'urifile'));
    }
}
