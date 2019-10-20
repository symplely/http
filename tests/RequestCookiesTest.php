<?php

namespace Async\Tests;

use Async\Http\Cookie;
use Async\Http\Cookies;
use Async\Http\Request;
use Async\Http\RequestCookies;
use PHPUnit\Framework\TestCase;

class RequestCookiesTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_cookies() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        self::assertEquals(
            'RAPELCGRQ',
            RequestCookies::get($request, 'sessionToken')->getValue()
        );
    }

    /**
     * @test
     */
    public function it_sets_cookies() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $request = RequestCookies::set($request, Cookie::make('hello', 'WORLD!'));

        self::assertEquals(
            'theme=light; sessionToken=RAPELCGRQ; hello=WORLD%21',
            $request->getHeaderLine('Cookie')
        );
    }

    /**
     * @test
     */
    public function it_modifies_cookies() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $request = RequestCookies::modify($request, 'hello', function (Cookie $cookie) {
            return $cookie->withValue(strtoupper($cookie->getName()));
        });

        self::assertEquals(
            'theme=light; sessionToken=RAPELCGRQ; hello=HELLO',
            $request->getHeaderLine('Cookie')
        );
    }

    /**
     * @test
     */
    public function it_removes_cookies() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $request = RequestCookies::remove($request, 'sessionToken');

        self::assertEquals(
            'theme=light; hello=world',
            $request->getHeaderLine('Cookie')
        );
    }
}
