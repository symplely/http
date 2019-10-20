<?php

namespace Async\Tests;

use Async\Http\SetCookie;
use Async\Http\SetCookies;
use Async\Http\Response;
use Async\Http\ResponseCookies;
use PHPUnit\Framework\TestCase;

class ResponseCookiesTest extends TestCase
{
    public function testGet() : void
    {
        $response = (new Response());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $this->assertEquals(
            'ENCRYPTED',
            ResponseCookies::get($response, 'sessionToken')->getValue()
        );
    }

    public function testGetNull() : void
    {
        $response = (new Response());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $this->assertEquals(
            'ENCRYPTED',
            ResponseCookies::get($response, 'sessionToken', 'ENCRYPTED')->getValue()
        );
    }

    public function testExpire() : void
    {
        $response = (new Response());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $response = ResponseCookies::expire($response, 'sessionToken');

        $this->assertEquals(
            '',
            ResponseCookies::get($response, 'sessionToken')->getValue()
        );
    }

    public function testSet() : void
    {
        $response = (new Response());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $response = ResponseCookies::set($response, SetCookie::create('hello', 'WORLD!'));

        $this->assertEquals(
            'theme=light,sessionToken=ENCRYPTED,hello=WORLD%21',
            $response->getHeaderLine('Set-Cookie')
        );
    }

    public function testModify() : void
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

        $this->assertEquals(
            'theme=light,sessionToken=ENCRYPTED,hello=HELLO',
            $response->getHeaderLine('Set-Cookie')
        );
    }

    public function testRemove() : void
    {
        $response = (new Response());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $response = ResponseCookies::remove($response, 'sessionToken');

        $this->assertEquals(
            'theme=light,hello=world',
            $response->getHeaderLine('Set-Cookie')
        );
    }
}
