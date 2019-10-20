<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\Cookie;
use Async\Http\Cookies;
use Psr\Http\Message\RequestInterface;

class RequestCookies
{
    public static function get(RequestInterface $request, string $name, ?string $value = null) : Cookie
    {
        $cookies = Cookies::fromRequest($request);
        $cookie  = $cookies->get($name);

        if ($cookie) {
            return $cookie;
        }

        return Cookie::make($name, $value);
    }

    public static function set(RequestInterface $request, Cookie $cookie) : RequestInterface
    {
        return Cookies::fromRequest($request)
            ->with($cookie)
            ->intoHeader($request)
        ;
    }

    public static function modify(RequestInterface $request, string $name, callable $modify) : RequestInterface
    {
        if (! \is_callable($modify)) {
            throw new \InvalidArgumentException('$modify must be callable.');
        }

        $cookies = Cookies::fromRequest($request);
        $cookie  = $modify($cookies->has($name)
            ? $cookies->get($name)
            : Cookie::make($name));

        return $cookies
            ->with($cookie)
            ->intoHeader($request)
        ;
    }

    public static function remove(RequestInterface $request, string $name) : RequestInterface
    {
        return Cookies::fromRequest($request)
            ->without($name)
            ->intoHeader($request)
        ;
    }
}
