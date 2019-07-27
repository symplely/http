<?php

namespace Async\Tests;

use Async\Http\Uri;
use Async\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testMethod()
    {
        $request = new Request();
        $this->assertNotEmpty($request->getMethod());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('PUT', $request->withMethod('PUT')->getMethod());
    }

    public function testMethodInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Request('something');
    }

    public function testRequestTarget()
    {
        $request = new Request();
        $this->assertNotEmpty($request->getRequestTarget());
        $this->assertEquals('/', $request->getRequestTarget());
        $this->assertEquals(
            '/user/profile',
            $request->withRequestTarget('/user/profile')
                ->getRequestTarget()
        );
        $uri = new Uri();
        $this->assertEquals(
            '/subdir',
            $request->withUri($uri->createUri('http://domain.tld/subdir'))
                ->getRequestTarget()
        );
        $this->assertEquals(
            '/subdir?test=true',
            $request->withUri($uri->createUri('http://domain.tld/subdir?test=true'))
                ->getRequestTarget()
        );
    }

    public function testUri()
    {
        $request = new Request();
        $this->assertSame($uri = new Uri(), $request->withUri($uri)->getUri());
    }

    public function testUriPreserveHost()
    {
        $request = new Request();
        $factory = new Uri();
        $request = $request->withUri($factory->createUri('http://domain.tld:9090'), true);
        $this->assertEquals('domain.tld:9090', $request->getHeaderLine('Host'));
        $request = $request->withUri($factory->createUri('http://otherdomain.tld'), true);
        $this->assertEquals('domain.tld:9090', $request->getHeaderLine('Host'));
    }

    public function testCreateRequest()
    {
        $factory = new Request();
        $request = $factory->createRequest('GET', 'http://domain.tld:9090/subdir?test=true#phpunit');
        $this->assertInstanceOf('Psr\\Http\\Message\\RequestInterface', $request);
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertInstanceOf('Psr\\Http\\Message\\UriInterface', $uri = $request->getUri());
        $this->assertEquals('http://domain.tld:9090/subdir?test=true#phpunit', (string)$uri);
    }
}
