<?php

namespace Async\Tests;

use Async\Http\Cookie;
use Async\Http\Cookies;
use Async\Http\Request;
use Async\Http\RequestCookies;
use PHPUnit\Framework\TestCase;

class RequestCookiesTest extends TestCase
{
    public function testGet() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $this->assertEquals(
            'RAPELCGRQ',
            RequestCookies::get($request, 'sessionToken')->getValue()
        );
    }

    public function testGetNull() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; hello=world')
        ;

        $this->assertEquals(
            '',
            RequestCookies::get($request, 'session', '')->getValue()
        );
    }

    public function testSet() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $request = RequestCookies::set($request, Cookie::make('hello', 'WORLD!'));

        $this->assertEquals(
            'theme=light; sessionToken=RAPELCGRQ; hello=WORLD%21',
            $request->getHeaderLine('Cookie')
        );
    }

    public function testModify() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $request = RequestCookies::modify($request, 'hello', function (Cookie $cookie) {
            return $cookie->withValue(strtoupper($cookie->getName()));
        });

        $this->assertEquals(
            'theme=light; sessionToken=RAPELCGRQ; hello=HELLO',
            $request->getHeaderLine('Cookie')
        );
    }

    public function testRemove() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $request = RequestCookies::remove($request, 'sessionToken');

        $this->assertEquals(
            'theme=light; hello=world',
            $request->getHeaderLine('Cookie')
        );
    }
}
