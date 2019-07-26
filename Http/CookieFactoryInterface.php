<?php

namespace Async\Http;

/**
 * Interface CookieFactoryInterface
 *
 * @package Async\Http
 */
interface CookieFactoryInterface
{
    /**
     * @param string $header
     * @return CookieInterface
     */
    public function createCookie($header);
}
