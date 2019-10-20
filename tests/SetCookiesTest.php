<?php

namespace Async\Tests;

use Async\Http\Response;
use Async\Http\SetCookie;
use Async\Http\SetCookies;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;

class SetCookiesTest extends TestCase
{
    public const INTERFACE_PSR_HTTP_MESSAGE_RESPONSE = ResponseInterface::class;

    /**
     * @dataProvider provideSetCookieStringsAndExpectedSetCookiesData
     */
    public function testFromResponse(array $setCookieStrings, array $expectedSetCookies) : void
    {
        $response = $this->prophesize(self::INTERFACE_PSR_HTTP_MESSAGE_RESPONSE);
        $response->getHeader(SetCookies::SET_COOKIE_HEADER)->willReturn($setCookieStrings);

        $setCookies = SetCookies::fromResponse($response->reveal());

        $this->assertEquals($expectedSetCookies, $setCookies->getAll());
    }

    /**
     * @dataProvider provideSetCookieStringsAndExpectedSetCookiesData
     */
    public function testFromStrings(array $setCookieStrings, array $expectedSetCookies) : void
    {
        $setCookies = SetCookies::fromStrings($setCookieStrings);

        $this->assertEquals($expectedSetCookies, $setCookies->getAll());
    }

    /**
     * @dataProvider provideSetCookieStringsAndExpectedSetCookiesData
     */
    public function testFromStringsHas(array $setCookieStrings, array $expectedSetCookies) : void
    {
        $setCookies = SetCookies::fromStrings($setCookieStrings);

        foreach ($expectedSetCookies as $expectedSetCookie) {
            $this->assertTrue($setCookies->has($expectedSetCookie->getName()));
        }

        $this->assertFalse($setCookies->has('i know this cookie does not exist'));
    }

    /**
     * @dataProvider provideGetsSetCookieByNameData
     */
    public function testFromStringsGet(array $setCookieStrings, string $setCookieName, ?SetCookie $expectedSetCookie = null) : void
    {
        $setCookies = SetCookies::fromStrings($setCookieStrings);

        $this->assertEquals($expectedSetCookie, $setCookies->get($setCookieName));
    }

    public function testIntoHeaderAddRemove() : void
    {
        $setCookies = SetCookies::fromStrings(['theme=light', 'sessionToken=abc123', 'hello=world'])
            ->with(SetCookie::create('theme', 'blue'))
            ->without('sessionToken')
            ->with(SetCookie::create('who', 'me'))
        ;

        $originalResponse = new Response();
        $response         = $setCookies->intoHeader($originalResponse);

        $this->assertNotEquals($response, $originalResponse);

        $this->assertEquals(
            ['theme=blue', 'hello=world', 'who=me'],
            $response->getHeader(SetCookies::SET_COOKIE_HEADER)
        );
    }

    public function testIntoHeaderGetUpdateOnRequest() : void
    {
        $response = (new Response())
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, 'theme=light')
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, 'sessionToken=ENCRYPTED')
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, 'hello=world')
        ;

        // Get our set cookies from the response.
        $setCookies = SetCookies::fromResponse($response);

        // Ask for the encrypted session token.
        $decryptedSessionToken = $setCookies->get('sessionToken');

        // Get the encrypted value from the cookie and decrypt it.
        $decryptedValue = $decryptedSessionToken->getValue();
        $encryptedValue = \str_rot13($decryptedValue);

        // Create a new set cookie with the encrypted value.
        $encryptedSessionToken = $decryptedSessionToken->withValue($encryptedValue);

        // Include our encrypted session token with the rest of our cookies.
        $setCookies = $setCookies->with($encryptedSessionToken);

        // Render our cookies, along with the newly decrypted session token, into a response.
        $response = $setCookies->intoHeader($response);

        // From this point on, any response based on this one can get the encrypted version
        // of the session token.
        $this->assertEquals(
            ['theme=light', 'sessionToken=RAPELCGRQ', 'hello=world'],
            $response->getHeader(SetCookies::SET_COOKIE_HEADER)
        );
    }

    public function provideSetCookieStringsAndExpectedSetCookiesData()
    {
        return [
            [
                [],
                [],
            ],
            [
                ['someCookie='],
                [
                    SetCookie::create('someCookie'),
                ],
            ],
            [
                [
                    'someCookie=someValue',
                    'LSID=DQAAAK%2FEaem_vYg; Path=/accounts; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                ],
                [
                    SetCookie::create('someCookie', 'someValue'),
                    SetCookie::create('LSID')
                        ->withValue('DQAAAK/Eaem_vYg')
                        ->withPath('/accounts')
                        ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                        ->withSecure(true)
                        ->withHttpOnly(true),
                ],
            ],
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                ],
                [
                    SetCookie::create('a', 'AAA'),
                    SetCookie::create('b', 'BBB'),
                    SetCookie::create('c', 'CCC'),
                ],
            ],
        ];
    }

    public function provideGetsSetCookieByNameData() : array
    {
        return [
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                ],
                'b',
                SetCookie::create('b', 'BBB'),
            ],
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                    'LSID=DQAAAK%2FEaem_vYg; Path=/accounts; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                ],
                'LSID',
                SetCookie::create('LSID')
                    ->withValue('DQAAAK/Eaem_vYg')
                    ->withPath('/accounts')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                ],
                'LSID',
                null,
            ],
        ];
    }
}
