<?php

namespace Async\Tests;

use Async\Http\SetCookie;
use Async\Http\SetCookies;
use Async\Http\Response;
use Async\Http\ResponseCookies;
use PHPUnit\Framework\TestCase;

class ResponseCookiesTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_cookies() : void
    {
        $response = (new Response());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        self::assertEquals(
            'ENCRYPTED',
            ResponseCookies::get($response, 'sessionToken')->getValue()
        );
    }

    /**
     * @test
     */
    public function it_sets_cookies() : void
    {
        $response = (new Response());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $response = ResponseCookies::set($response, SetCookie::create('hello', 'WORLD!'));

        self::assertEquals(
            'theme=light,sessionToken=ENCRYPTED,hello=WORLD%21',
            $response->getHeaderLine('Set-Cookie')
        );
    }

    /**
     * @test
     */
    public function it_modifies_cookies() : void
    {
        $response = (new Response());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $response = ResponseCookies::modify($response, 'hello', function (SetCookie $setCookie) {
            return $setCookie->withValue(strtoupper($setCookie->getName()));
        });

        self::assertEquals(
            'theme=light,sessionToken=ENCRYPTED,hello=HELLO',
            $response->getHeaderLine('Set-Cookie')
        );
    }

    /**
     * @test
     */
    public function it_removes_cookies() : void
    {
        $response = (new Response());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $response = ResponseCookies::remove($response, 'sessionToken');

        self::assertEquals(
            'theme=light,hello=world',
            $response->getHeaderLine('Set-Cookie')
        );
    }
}
