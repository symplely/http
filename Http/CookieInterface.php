<?php

namespace Async\Http;

/**
 * Interface CookieInterface
 * 
 * @package Async\Http
 */
interface CookieInterface
{
    /**
     * @return string
     */
    public function getDomain();

    /**
     * @return \DateTimeInterface
     */
    public function getExpiry();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return string
     */
    public function getValue();

    /**
     * @return bool
     */
    public function isHttpOnly();

    /**
     * @return bool
     */
    public function isSecure();

    /**
     * @param string|null $domain
     * @return static
     */
    public function withDomain($domain);

    /**
     * @param \DateTimeInterface|string|int|null $expiry
     * @return static
     */
    public function withExpiry($expiry);

    /**
     * @param bool $flag
     * @return static
     */
    public function withHttpOnly($flag);

    /**
     * @param string $name
     * @return static
     */
    public function withName($name);

    /**
     * @param string|null $path
     * @return static
     */
    public function withPath($path);

    /**
     * @param bool $flag
     * @return static
     */
    public function withSecure($flag);

    /**
     * @param string|null $value
     * @return static
     */
    public function withValue($value);
}
