<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\SetCookie;
use Async\Http\SetCookies;
use Psr\Http\Message\ResponseInterface;

class ResponseCookies
{
    public static function get(ResponseInterface $response, string $name, ?string $value = null): SetCookie
    {
        $setCookies = SetCookies::fromResponse($response);
        $cookie     = $setCookies->get($name);

        if ($cookie) {
            return $cookie;
        }

        return SetCookie::create($name, $value);
    }

    public static function set(ResponseInterface $response, SetCookie $setCookie): ResponseInterface
    {
        return SetCookies::fromResponse($response)
            ->with($setCookie)
            ->intoHeader($response);
    }

    public static function expire(ResponseInterface $response, string $cookieName): ResponseInterface
    {
        return self::set($response, SetCookie::createExpired($cookieName));
    }

    public static function modify(ResponseInterface $response, string $name, callable $modify): ResponseInterface
    {
        $setCookies = SetCookies::fromResponse($response);
        $setCookie  = $modify($setCookies->has($name)
            ? $setCookies->get($name)
            : SetCookie::create($name));

        return $setCookies
            ->with($setCookie)
            ->intoHeader($response);
    }

    public static function remove(ResponseInterface $response, string $name): ResponseInterface
    {
        return SetCookies::fromResponse($response)
            ->without($name)
            ->intoHeader($response);
    }
}
