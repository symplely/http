<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\Cookie;
use Psr\Http\Message\RequestInterface;

class Cookies
{
    /**
     * The name of the Cookie header.
     */
    public const COOKIE_HEADER = 'Cookie';

    private $cookies = [];

    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $cookie) {
            $this->cookies[$cookie->getName()] = $cookie;
        }
    }

    public function has(string $name) : bool
    {
        return isset($this->cookies[$name]);
    }

    public function get(string $name) : ?Cookie
    {
        if (! $this->has($name)) {
            return null;
        }

        return $this->cookies[$name];
    }

    public function getAll() : array
    {
        return \array_values($this->cookies);
    }

    public function with(Cookie $cookie) : Cookies
    {
        $clone = clone($this);

        $clone->cookies[$cookie->getName()] = $cookie;

        return $clone;
    }

    public function without(string $name) : Cookies
    {
        $clone = clone($this);

        if (! $clone->has($name)) {
            return $clone;
        }

        unset($clone->cookies[$name]);

        return $clone;
    }

    /**
     * Put Cookies into a Request.
     */
    public function intoHeader(RequestInterface $request) : RequestInterface
    {
        $cookieString = \implode('; ', $this->cookies);

        $request = $request->withHeader(self::COOKIE_HEADER, $cookieString);

        return $request;
    }

    /**
     * Create Cookies from a Cookie header value string.
     */
    public static function fromString(string $string) : self
    {
        return new self(Cookie::listFromString($string));
    }

    /**
     * Get Cookies from a Request.
     */
    public static function fromRequest(RequestInterface $request) : Cookies
    {
        $cookieString = $request->getHeaderLine(self::COOKIE_HEADER);

        return self::fromString($cookieString);
    }
}
