<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\Cookie;
use Async\Http\CookieFactoryInterface;

/**
 * Class CookieFactory
 *
 * @package Async\Http
 */
class CookieFactory implements CookieFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createCookie($header)
    {
        $parts = \preg_split('~\\s*[;]\\s*~', $header);
        list($name, $value) = \explode('=', \array_shift($parts), 2);
        $cookie = new Cookie($name);
        if (\is_string($value)) {
            $cookie = $cookie->withValue(\urldecode($value));
        }
        while ($nvp = \array_shift($parts)) {
            $nvp = \explode('=', $nvp, 2);
            $value = \count($nvp) === 2 ? $nvp[1] : null;
            switch (\strtolower($nvp[0])) {
                case 'domain':
                    $cookie = $cookie->withDomain($value);
                    break;
                case 'expires':
                    $cookie = $cookie->withExpiry($value);
                    break;
                case 'httponly':
                    $cookie = $cookie->withHttpOnly(true);
                    break;
                case 'max-age':
                    $cookie = $cookie->withMaxAge($value);
                    break;
                case 'path':
                    $cookie = $cookie->withPath($value);
                    break;
                case 'secure':
                    $cookie = $cookie->withSecure(true);
                    break;
            }
        }
        return $cookie;
    }
}
