<?php

namespace Async\Tests;

use Async\Http\SetCookie;
use PHPUnit\Framework\TestCase;

class SetCookieTest extends TestCase
{
    /**
     * @dataProvider provideParsesFromSetCookieStringData
     */
    public function testFromString(string $cookieString, SetCookie $expectedSetCookie) : void
    {
        $setCookie = SetCookie::fromString($cookieString);

        self::assertEquals($expectedSetCookie, $setCookie);
        self::assertEquals($cookieString, (string) $setCookie);
    }

    public function provideParsesFromSetCookieStringData() : array
    {
        return [
            [
                'someCookie=',
                SetCookie::create('someCookie'),
            ],
            [
                'someCookie=someValue',
                SetCookie::create('someCookie')
                    ->withValue('someValue'),
            ],
            [
                'LSID=DQAAAK%2FEaem_vYg; Path=/accounts; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                SetCookie::create('LSID')
                    ->withValue('DQAAAK/Eaem_vYg')
                    ->withPath('/accounts')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                'HSID=AYQEVn%2F.DKrdst; Domain=.foo.com; Path=/; Expires=Wed, 13 Jan 2021 22:23:01 GMT; HttpOnly',
                SetCookie::create('HSID')
                    ->withValue('AYQEVn/.DKrdst')
                    ->withDomain('.foo.com')
                    ->withPath('/')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withHttpOnly(true),
            ],
            [
                'SSID=Ap4P%2F.GTEq; Domain=foo.com; Path=/; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                SetCookie::create('SSID')
                    ->withValue('Ap4P/.GTEq')
                    ->withDomain('foo.com')
                    ->withPath('/')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; HttpOnly',
                SetCookie::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withExpires('Tue, 15-Jan-2013 21:47:38 GMT')
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Max-Age=500; Secure; HttpOnly',
                SetCookie::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withMaxAge(500)
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly',
                SetCookie::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withExpires('Tue, 15-Jan-2013 21:47:38 GMT')
                    ->withMaxAge(500)
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly',
                SetCookie::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withExpires(1358286458)
                    ->withMaxAge(500)
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly',
                SetCookie::create('lu')
                         ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                         ->withExpires(new \DateTime('Tue, 15-Jan-2013 21:47:38 GMT'))
                         ->withMaxAge(500)
                         ->withPath('/')
                         ->withDomain('.example.com')
                         ->withSecure(true)
                         ->withHttpOnly(true),
            ],
        ];
    }

    public function testExpireCookies() : void
    {
        $setCookie = SetCookie::createExpired('expire_immediately');

        self::assertLessThan(\time(), $setCookie->getExpires());
    }

    public function TestLongLivingCookies() : void
    {
        $setCookie = SetCookie::createRememberedForever('remember_forever');

        $fourYearsFromNow = (new \DateTime('+4 years'))->getTimestamp();
        self::assertGreaterThan($fourYearsFromNow, $setCookie->getExpires());
    }

    public function TestInvalid() : void
    {
        $setCookie = SetCookie::create('foo', 'bar');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid expires "potato" provided');

        $setCookie->withExpires('potato');
    }

    public function testEmpty() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided cookie string "" must have at least one attribute');

        SetCookie::fromString('');
    }
}
