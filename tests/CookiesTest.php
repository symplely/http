<?php

namespace Async\Tests;

use Async\Http\Cookie;
use Async\Http\Cookies;
use Async\Http\Request;
use Psr\Http\Message\RequestInterface;
use PHPUnit\Framework\TestCase;

class CookiesTest extends TestCase
{
    private const INTERFACE_PSR_HTTP_MESSAGE_REQUEST = RequestInterface::class;

    /**
     * @dataProvider provideCookieStringAndExpectedCookiesData
     */
    public function testFromRequest(string $cookieString, array $expectedCookies) : void
    {
        $request = $this->prophesize(self::INTERFACE_PSR_HTTP_MESSAGE_REQUEST);
        $request->getHeaderLine(Cookies::COOKIE_HEADER)->willReturn($cookieString);

        $cookies = Cookies::fromRequest($request->reveal());

        $this->assertEquals($expectedCookies, $cookies->getAll());
    }

    /**
     * @dataProvider provideCookieStringAndExpectedCookiesData
     */
    public function testFromString(string $cookieString, array $expectedCookies) : void
    {
        $cookies = Cookies::fromString($cookieString);

        $this->assertEquals($expectedCookies, $cookies->getAll());
    }

    /**
     * @dataProvider provideCookieStringAndExpectedCookiesData
     */
    public function testFromStringHas(string $cookieString, array $expectedCookies) : void
    {
        $cookies = Cookies::fromString($cookieString);

        foreach ($expectedCookies as $expectedCookie) {
            $this->assertTrue($cookies->has($expectedCookie->getName()));
        }

        $this->assertFalse($cookies->has('i know this cookie does not exist'));
    }

    /**
     * @dataProvider provideGetsCookieByNameData
     */
    public function testFromStringGet(string $cookieString, string $cookieName, Cookie $expectedCookie) : void
    {
        $cookies = Cookies::fromString($cookieString);

        $this->assertEquals($expectedCookie, $cookies->get($cookieName));
    }

    public function testCookies() : void
    {
        $cookies = new Cookies();

        $cookies = $cookies->with(Cookie::make('theme', 'blue'));

        $this->assertEquals('blue', $cookies->get('theme')->getValue());

        $cookies = $cookies->with(Cookie::make('theme', 'red'));

        $this->assertEquals('red', $cookies->get('theme')->getValue());

        $cookies = $cookies->without('theme');

        $this->assertFalse($cookies->has('theme'));
    }

    public function testCookiesIntoHeader() : void
    {
        $cookies = (new Cookies())
            ->with(Cookie::make('theme', 'light'))
            ->with(Cookie::make('sessionToken', 'abc123'))
        ;

        $originalRequest = new Request();
        $request         = $cookies->intoHeader($originalRequest);

        $this->assertNotEquals($request, $originalRequest);

        $this->assertEquals('theme=light; sessionToken=abc123', $request->getHeaderLine(Cookies::COOKIE_HEADER));
    }

    public function testCookiesIntoHeaderAddRemove() : void
    {
        $cookies = Cookies::fromString('theme=light; sessionToken=abc123; hello=world')
            ->with(Cookie::make('theme', 'blue'))
            ->without('sessionToken')
            ->with(Cookie::make('who', 'me'))
        ;

        $originalRequest = new Request();
        $request         = $cookies->intoHeader($originalRequest);

        $this->assertNotEquals($request, $originalRequest);

        $this->assertEquals('theme=blue; hello=world; who=me', $request->getHeaderLine(Cookies::COOKIE_HEADER));
    }

    public function testFromRequestGetValue() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $theme = Cookies::fromRequest($request)->get('theme')->getValue();

        $this->assertEquals('light', $theme);
    }

    public function testFromRequestUpdateValue() : void
    {
        $request = (new Request())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        // Get our cookies from the request.
        $cookies = Cookies::fromRequest($request);

        // Ask for the encrypted session token.
        $encryptedSessionToken = $cookies->get('sessionToken');

        // Get the encrypted value from the cookie and decrypt it.
        $encryptedValue = $encryptedSessionToken->getValue();
        $decryptedValue = \str_rot13($encryptedValue);

        // Create a new cookie with the decrypted value.
        $decryptedSessionToken = $encryptedSessionToken->withValue($decryptedValue);

        // Include our decrypted session token with the rest of our cookies.
        $cookies = $cookies->with($decryptedSessionToken);

        // Render our cookies, along with the newly decrypted session token, into a request.
        $request = $cookies->intoHeader($request);

        // From this point on, any request based on this one can get the plaintext version
        // of the session token.
        $this->assertEquals(
            'theme=light; sessionToken=ENCRYPTED; hello=world',
            $request->getHeaderLine(Cookies::COOKIE_HEADER)
        );
    }

    public function provideCookieStringAndExpectedCookiesData() : array
    {
        return [
            [
                '',
                [],
            ],
            [
                'theme=light',
                [
                    Cookie::make('theme', 'light'),
                ],
            ],
            [
                'theme=light; sessionToken=abc123',
                [
                    Cookie::make('theme', 'light'),
                    Cookie::make('sessionToken', 'abc123'),
                ],
            ],
        ];
    }

    public function provideGetsCookieByNameData()
    {
        return [
            ['theme=light', 'theme', Cookie::make('theme', 'light')],
            ['theme=', 'theme', Cookie::make('theme')],
            ['hello=world; theme=light; sessionToken=abc123', 'theme', Cookie::make('theme', 'light')],
            ['hello=world; theme=; sessionToken=abc123', 'theme', Cookie::make('theme')],
        ];
    }
}
