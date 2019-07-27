<?php

declare(strict_types=1);

namespace Async\Http;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Class MessageValidations
 *
 * @package Async\Http
 */
class MessageValidations
{
    const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~\pL';

    /**
     * MessageValidations constructor.
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @param string $path
     */
    public static function assertPath($path)
    {
        if (false !== \stripos($path, '?')) {
            throw new \InvalidArgumentException('$path must not contain query parameters');
        } elseif (false !== \stripos($path, '#')) {
            throw new \InvalidArgumentException('$path must not contain hash fragment');
        }
    }

    /**
     * @param string $query
     */
    public static function assertQuery($query)
    {
        if (false !== \stripos($query, '#')) {
            throw new \InvalidArgumentException('$query must not contain hash fragment');
        }
    }

    /**
     * @param int $port
     */
    public static function assertTcpUdpPort($port)
    {
        if ((0 > $port) || (65535 <= $port)) {
            throw new \InvalidArgumentException('$port must be a valid integer within TCP/UDP port range');
        }
    }

    /**
     * @param string $fragment
     * @return string
     */
    public static function normalizeFragment($fragment)
    {
        if ($fragment && (0 === \stripos($fragment, '#'))) {
            $fragment = '%23' . \substr($fragment, 1);
        }
        return self::normalizeQueryOrFragment($fragment);
    }

    /**
     * @param string $query
     * @return string
     */
    public static function normalizeQuery($query)
    {
        if ($query && (0 === \stripos($query, '?'))) {
            $query = \substr($query, 1);
        }
        $nvps = \explode('&', $query);
        foreach ($nvps as $i => $nvp) {
            $pair = \explode('=', $nvp, 2);
            if (\count($pair) === 1) {
                $pair[] = null;
            }
            list($name, $value) = $pair;
            if (\is_null($value)) {
                $nvps[$i] = self::normalizeQueryOrFragment($name);
                continue;
            }
            $nvps[$i] = \sprintf('%s=%s', self::normalizeQueryOrFragment($name), self::normalizeQueryOrFragment($value));
        }
        return \implode('&', $nvps);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function normalizeQueryOrFragment($string)
    {
        return \preg_replace_callback(
            '#(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))#u',
            array(__CLASS__, 'rawUrlEncodeSubject'),
            $string
        );
    }

    /**
     * @param string $path
     * @return string
     */
    public static function normalizePath($path)
    {
        $path = \preg_replace_callback(
            '#(?:[^' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))#u',
            array(__CLASS__, 'rawUrlEncodeSubject'),
            $path
        );
        if ($path && ('/' === $path[0])) {
            $path = ('/' . \ltrim($path, '/'));
        }
        return $path;
    }

    /**
     * @param string $scheme
     * @return string
     */
    public static function normalizeScheme($scheme)
    {
        return \preg_replace('~:(//)?$~', '', \strtolower($scheme));
    }

    /**
     * @param array $matches
     * @return string
     */
    private static function rawUrlEncodeSubject(array $matches)
    {
        return \rawurlencode($matches[0]);
    }
}
