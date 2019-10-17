<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\Uri;
use Async\Http\ServerRequest;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * Class ServerRequestFactory
 *
 * @package Async\Http
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (\is_string($uri)) {
            $factory = new Uri();
            $uri = $factory->create($uri);
        }
        return new ServerRequest($method, $uri);
    }

    /**
     * {@inheritdoc}
     */
    public function createServerRequestFromArray(array $server)
    {
        $body = self::getPhpInputStream();
        $method = $server['REQUEST_METHOD'];
        $protocolVersion = self::getProtocolVersion($server);
        $uri = self::getUri($server);
        $request = new ServerRequest($method, $uri);
        $request = $request->withBody($body)
            ->withProtocolVersion($protocolVersion)
            ->withServerParams($server);
        $headers = self::getHeaders($server);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        return $request;
    }

    /**
     * @param array $server
     * @return array
     */
    protected static function getHeaders(array $server)
    {
        $headers = array();
        static $pick = array('CONTENT_', 'HTTP_');
        foreach ($server as $key => $value) {
            if (!$value) {
                continue;
            }
            if (\strpos($key, 'REDIRECT_') === 0) {
                $key = \substr($key, 9);
                if (\array_key_exists($key, $server)) {
                    continue;
                }
            }
            foreach ($pick as $prefix) {
                if (\strpos($key, $prefix) === 0) {
                    if ($prefix !== $pick[0]) {
                        $key = \substr($key, \strlen($prefix));
                    }
                    $key = \strtolower(\strtr($key, '_', '-'));
                    $headers[$key] = $value;
                    continue;
                }
            }
        }
        return $headers;
    }

    /**
     * @return StreamInterface
     */
    protected static function getPhpInputStream()
    {
        $temp = \fopen('php://temp', 'w+');
        \stream_copy_to_stream($input = \fopen('php://input', 'r'), $temp);
        \fclose($input);
        $stream = new Stream($temp);
        $stream->rewind();
        return $stream;
    }

    /**
     * @param array $server
     * @return string
     */
    protected static function getProtocolVersion(array $server)
    {
        if (isset($server['SERVER_PROTOCOL'])) {
            return \str_replace('HTTP/', '', $server['SERVER_PROTOCOL']);
        }

        return '1.1';
    }

    /**
     * @param array $server
     * @return UriInterface
     */
    public static function getUri(array $server)
    {
        $uri = new Uri();
        $scheme = isset($server['HTTPS']) && ($server['HTTPS'] === 'on') ? 'https' : 'http';
        $uri = $uri->withScheme($scheme);
        if (\array_key_exists('HTTP_HOST', $server)) {
            $host = $server['HTTP_HOST'];
            if (\preg_match('~(?P<host>[^:]+):(?P<port>\d+)$~', $host, $matches)) {
                $uri = $uri->withHost($matches['host'])
                    ->withPort((int)$matches['port']);
            } else {
                $uri = $uri->withHost($host);
            }
        } elseif (isset($server['SERVER_NAME'])) {
            $uri = $uri->withHost($server['SERVER_NAME']);
            if (\array_key_exists('SERVER_PORT', $server)) {
                $uri = $uri->withPort((int)$server['SERVER_PORT']);
            }
        }
        $path = null;
        if (isset($server['REQUEST_URI'])) {
            $path = $server['REQUEST_URI'];
        }
        if (isset($server['HTTP_X_REWRITE_URL'])) {
            $path = $server['HTTP_X_REWRITE_URL'];
        }
        if (isset($server['HTTP_X_ORIGINAL_URL'])) {
            $path = $server['HTTP_X_ORIGINAL_URL'];
        }
        if ($path !== null) {
            if (\preg_match('~^[^/:]+://[^/]+(?P<path>.*)$~', $path, $matches)) {
                $path = $matches['path'];
            }
        } elseif (isset($server['ORIG_PATH_INFO'])) {
            $path = $server['ORIG_PATH_INFO'];
        }
        if (empty($path)) {
            $path = '/';
        }
        if (\stripos($path, '#') !== false) {
            list($path, $fragment) = \explode('#', $path, 2);
            $uri = $uri->withFragment($fragment);
        }
        if (($i = stripos($path, '?')) !== false) {
            $path = substr($path, 0, $i);
        }
        if (isset($server['QUERY_STRING'])) {
            $query = \ltrim($server['QUERY_STRING'], '?');
            $uri = $uri->withQuery($query);
        }
        $uri = $uri->withPath($path);
        return $uri;
    }

    /**
     * Create new ServerRequest from environment.
     *
     * @internal This method is not part of PSR-17
     *
     * @return ServerRequestInterface
     */
    public static function fromGlobals(): ServerRequestInterface
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri = Uri::fromGlobals($_SERVER);

        $headers = self::requestAllHeaders();
        $cookies = Cookie::parseCookieHeader((isset($headers['Cookie']) ? $headers['Cookie'] : ''));

        $body = Stream::createFromFile('php://input', 'r');
        $uploadedFiles = UploadedFile::fromGlobals($_SERVER);

        $request = new ServerRequest($method, $uri, $headers, $cookies, $_SERVER, $body, $uploadedFiles);
        $contentTypes = $request->getHeader('Content-Type') ?? [];

        $parsedContentType = '';
        foreach ($contentTypes as $contentType) {
            $fragments = \explode(';', $contentType);
            $parsedContentType = \current($fragments);
        }

        $contentTypesWithParsedBodies = ['application/x-www-form-urlencoded', 'multipart/form-data'];
        if ($method === 'POST' && \in_array($parsedContentType, $contentTypesWithParsedBodies)) {
            return $request->withParsedBody($_POST);
        }

        return $request;
    }

    /**
     * Get all HTTP header key/values as an associative array for the current request.
     *
     * @return array The HTTP header key/value pairs.
     */
    public static function requestAllHeaders()
    {
        $headers = [];
        $copy_server = array(
            'CONTENT_TYPE' => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5' => 'Content-Md5',
        );

        foreach ($_SERVER as $key => $value) {
            if (\substr($key, 0, 5) === 'HTTP_') {
                $key = \substr($key, 5);
                if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                    $key = \str_replace(' ', '-', \ucwords(\strtolower(\str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copy_server[$key])) {
                $headers[$copy_server[$key]] = $value;
            }
        }

        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . \base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }
}
