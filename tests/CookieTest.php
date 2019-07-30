<?php

namespace Async\Tests;

use Async\Http\Cookie;
use PHPUnit\Framework\TestCase;

class CookieTest extends TestCase
{
    public function testDomain()
    {
        $cookie = new Cookie($name = 'somename');
        $this->assertNull($cookie->getDomain());
        $cookie = $cookie->withDomain($domain = 'domain.tld');
        $this->assertEquals($domain, $cookie->getDomain());
    }

    public function testHttpOnly()
    {
        $cookie = new Cookie('somename');
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertTrue($cookie->withHttpOnly(true)->isHttpOnly());
    }

    public function testMaxAge()
    {
        $cookie = new Cookie('somename');
        $this->assertEquals(0, $cookie->getMaxAge());
        $cookie = $cookie->withMaxAge($age = 86400);
        $this->assertEquals($age, $cookie->getMaxAge());
    }

    public function testName()
    {
        $cookie = new Cookie($name = 'somename');
        $this->assertEquals($name, $cookie->getName());
        $cookie = $cookie->withName($name = 'othername');
        $this->assertEquals($name, $cookie->getName());
    }

    public function testPath()
    {
        $cookie = new Cookie($name = 'somename');
        $this->assertNull($cookie->getPath());
        $cookie = $cookie->withPath($path = '/');
        $this->assertEquals($path, $cookie->getPath());
    }

    public function testSecure()
    {
        $cookie = new Cookie('somename');
        $this->assertFalse($cookie->isSecure());
        $this->assertTrue($cookie->withSecure(true)->isSecure());
    }

    public function testValue()
    {
        $cookie = new Cookie('somename');
        $this->assertNull($cookie->getValue());
        $cookie = $cookie->withValue($value = 'somevalue');
        $this->assertEquals($value, $cookie->getValue());
        $cookie = $cookie->withValue(null);
        $this->assertNull($cookie->getValue());
    }

    public function testToString()
    {
        $time = new \DateTime();
        $cookie = new Cookie('PHPSESS');
        $expected = sprintf(
            'PHPSESS=1234567890; Domain=domain.tld; Expires=%s; HttpOnly; Max-Age=86400; Path=/admin; Secure',
            $time->format(Cookie::EXPIRY_FORMAT)
        );
        $this->assertEquals($expected, (string)$cookie->withValue('1234567890')
            ->withDomain('domain.tld')
            ->withExpiry($time)
            ->withHttpOnly(true)
            ->withMaxAge(86400)
            ->withPath('/admin')
            ->withSecure(true));
    }

    public function testBasic()
    {
        $cookie = Cookie::create('PHPSESS=1234567890');
        $this->assertEquals('PHPSESS', $cookie->getName());
        $this->assertEquals('1234567890', $cookie->getValue());
    }

    public function testWithAttributes()
    {
        $time = new \DateTime();
        $cookie = Cookie::create(sprintf(
            'PHPSESS=1234567890; Domain=domain.tld; Expires=%s; HttpOnly; Max-Age=86400; Path=/admin; Secure',
            $time->format(Cookie::EXPIRY_FORMAT)
        ));
        $this->assertEquals('domain.tld', $cookie->getDomain());
        $this->assertEquals(
            $time->format(Cookie::EXPIRY_FORMAT),
            $cookie->getExpiry()->format(Cookie::EXPIRY_FORMAT)
        );
        $this->assertEquals(86400, $cookie->getMaxAge());
        $this->assertEquals('PHPSESS', $cookie->getName());
        $this->assertEquals('/admin', $cookie->getPath());
        $this->assertEquals('1234567890', $cookie->getValue());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
    }
}
