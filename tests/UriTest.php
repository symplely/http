<?php

namespace Async\Tests;

use Async\Http\Uri;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase
{
    public function testAuthority()
    {
        $uri = new Uri();
        $this->assertEmpty($uri->getAuthority());
        $this->assertEquals(
            'domain.tld',
            $uri->withHost('domain.tld')
                ->getAuthority()
        );
        $this->assertEquals(
            'domain.tld:9090',
            $uri->withHost('domain.tld')
                ->withPort(9090)
                ->getAuthority()
        );
        $this->assertEquals(
            'someone@domain.tld:9090',
            $uri->withHost('domain.tld')
                ->withPort(9090)
                ->withUserInfo('someone')
                ->getAuthority()
        );
        $this->assertEquals(
            'someone:secret@domain.tld:9090',
            $uri->withHost('domain.tld')
                ->withPort(9090)
                ->withUserInfo('someone', 'secret')
                ->getAuthority()
        );
    }

    public function testFragment()
    {
        $uri = new Uri();
        $this->assertEmpty($uri->getFragment());
        $this->assertEquals(
            'phpunit',
            $uri->withFragment('phpunit')
                ->getFragment()
        );
        $this->assertEquals(
            '%23phpunit',
            $uri->withFragment('#phpunit')
                ->getFragment()
        );
        $this->assertEquals(
            'phpunit%20%5E4.0%20%7C%7C%20%5E5.0',
            $uri->withFragment('phpunit ^4.0 || ^5.0')
                ->getFragment()
        );
    }

    public function testHost()
    {
        $uri = new Uri();
        $this->assertEmpty($uri->getHost());
        $this->assertEquals(
            'domain.tld',
            $uri->withHost('domain.tld')
                ->getHost()
        );
        $this->assertEquals(
            'domain.tld',
            $uri->withHost('DOMAIN.tld')
                ->getHost()
        );
        $this->assertEquals(
            'domain.tld',
            $uri->withHost('domain.TLD')
                ->getHost()
        );
        $this->assertEquals(
            'domain.tld',
            $uri->withHost('DoMaIn.TlD')
                ->getHost()
        );
    }

    public function testPath()
    {
        $uri = new Uri();
        $this->assertEmpty($uri->getPath());
        $this->assertEquals(
            '/subdir',
            $uri->withPath('/subdir')
                ->getPath()
        );
        $this->assertEquals(
            '/subdir',
            $uri->withPath('//subdir')
                ->getPath()
        );
        $this->assertEquals(
            'subdir',
            $uri->withPath('subdir')
                ->getPath()
        );
    }

    public function testPathWithQuery()
    {
        $uri = new Uri();
        $this->expectException(\InvalidArgumentException::class);
        $uri->withPath('/subdir?test=true')
            ->getPath();
    }

    public function testPathWithFragment()
    {
        $uri = new Uri();
        $this->expectException(\InvalidArgumentException::class);
        $uri->withPath('/subdir#phpunit')
            ->getPath();
    }

    public function testPort()
    {
        $uri = new Uri();
        $this->assertNull($uri->getPort());
        $this->assertEquals(
            9090,
            $uri->withPort(9090)
                ->getPort()
        );
    }

    public function testPortInvalid()
    {
        $uri = new Uri();
        $this->expectException(\InvalidArgumentException::class);
        $uri->withPort(-999);
    }

    public function testQuery()
    {
        $uri = new Uri();
        $this->assertEmpty($uri->getQuery());
        $this->assertEquals(
            'test=true',
            $uri->withQuery('test=true')
                ->getQuery()
        );
        $this->assertEquals(
            'test=true',
            $uri->withQuery('?test=true')
                ->getQuery()
        );
        $this->assertEquals(
            'test=true&debug',
            $uri->withQuery('?test=true&debug')
                ->getQuery()
        );
    }

    public function testQueryInvalid()
    {
        $uri = new Uri();
        $this->expectException(\InvalidArgumentException::class);
        $uri->withQuery('test=true#phpunit');
    }

    public function testScheme()
    {
        $uri = new Uri();
        $this->assertEmpty($uri->getScheme());
        $this->assertEquals(
            'http',
            $uri->withScheme('http')
                ->getScheme()
        );
        $this->assertEquals(
            'https',
            $uri->withScheme('https')
                ->getScheme()
        );
        $this->assertEquals(
            'http',
            $uri->withScheme('http://')
                ->getScheme()
        );
    }

    public function testUserInfo()
    {
        $uri = new Uri();
        $this->assertEmpty($uri->getUserInfo());
        $this->assertEquals(
            'someone',
            $uri->withUserInfo('someone')
                ->getUserInfo()
        );
        $this->assertEquals(
            'someone:secret',
            $uri->withUserInfo('someone', 'secret')
                ->getUserInfo()
        );
        $this->assertEmpty(
            $uri->withUserInfo(null, 'secret')
                ->getScheme()
        );
    }

    public function testImmutability()
    {
        $uri = new Uri();
        $this->assertNotSame($uri, $uri->withFragment('phpunit'));
        $this->assertNotSame($uri, $uri->withHost('domain.tld'));
        $this->assertNotSame($uri, $uri->withPath('/subdir'));
        $this->assertNotSame($uri, $uri->withPort(9090));
        $this->assertNotSame($uri, $uri->withQuery('test=true'));
        $this->assertNotSame($uri, $uri->withScheme('http'));
        $this->assertNotSame($uri, $uri->withUserInfo('someone', 'secret'));
    }

    public function testToString()
    {
        $uri = new Uri();
        $uri = $uri->withFragment('phpunit')
            ->withHost('domain.tld')
            ->withPath('/subdir')
            ->withPort(9090)
            ->withQuery('test=true')
            ->withScheme('http')
            ->withUserInfo('someone', 'secret');
        $this->assertEquals('http://someone:secret@domain.tld:9090/subdir?test=true#phpunit', (string)$uri);
    }

    public function testCreate()
    {
        $factory = new Uri();
        $this->assertInstanceOf('Psr\\Http\\Message\\UriInterface', $uri = $factory->create());
        $this->assertEmpty((string)$uri);
        $uri = $factory->create($url = 'http://someone:secret@domain.tld:9090/subdir?test=true#phpunit');
        $this->assertInstanceOf('Psr\\Http\\Message\\UriInterface', $uri);
        $this->assertEquals('http', $uri->getScheme());
        $this->assertEquals('someone:secret', $uri->getUserInfo());
        $this->assertEquals('domain.tld', $uri->getHost());
        $this->assertEquals(9090, $uri->getPort());
        $this->assertEquals('someone:secret@domain.tld:9090', $uri->getAuthority());
        $this->assertEquals('/subdir', $uri->getPath());
        $this->assertEquals('test=true', $uri->getQuery());
        $this->assertEquals('phpunit', $uri->getFragment());
        $this->assertEquals($url, (string)$uri);
        $this->assertEquals($url, (string)$uri->withPath('subdir'));
    }

    public function testUriInvalidString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $factory = new Uri();
        $factory->create('http:///domain.tld/');
    }
}
