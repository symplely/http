<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\Request;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class ServerRequest
 *
 * @package Async\Http
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array
     */
    protected $attributes = array();

    /**
     * @var array
     */
    protected $cookieParams = array();

    /**
     * @var mixed
     */
    protected $parsedBody;

    /**
     * @var array
     */
    protected $queryParams = array();

    /**
     * @var array
     */
    protected $serverParams = array();

    /**
     * @var UploadedFileInterface[]
     */
    protected $uploadedFiles = array();

    /**
     * @param string           $method        The request method
     * @param UriInterface     $uri           The request URI object
     * @param HeadersInterface $headers       The request headers collection
     * @param array            $cookies       The request cookies collection
     * @param array            $serverParams  The server environment variables
     * @param StreamInterface  $body          The request body object
     * @param array            $uploadedFiles The request uploadedFiles collection
     * @throws InvalidArgumentException on invalid HTTP method
     */
    public function __construct(
        $method = 'GET',
        UriInterface $uri = null,
        array $headers = [],
        array $cookies = [],
        array $serverParams = [],
        StreamInterface $body = null,
        array $uploadedFiles = []
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->headers = $headers;
        $this->cookieParams = $cookies;
        $this->serverParams = $serverParams;
        $this->attributes = [];
        $this->body = $body;
        $this->uploadedFiles = $uploadedFiles;

        if (isset($serverParams['SERVER_PROTOCOL'])) {
            $this->protocolVersion = \str_replace('HTTP/', '', $serverParams['SERVER_PROTOCOL']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        return \array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        if ($this->parsedBody || !$this->body) {
            return $this->parsedBody;
        }
        $type = $this->getHeaderLine('Content-Type');
        if ($type) {
            list($type) = \explode(';', $type, 2);
        }
        $body = (string) $this->getBody();
        switch ($type) {
            case 'application/json':
                $this->parsedBody = \json_decode($body, true);
                break;
            case 'application/x-www-form-urlencoded':
                \parse_str($body, $data);
                $this->parsedBody = $data;
                break;
            case 'text/xml':
                $disabled = \libxml_disable_entity_loader(true);
                $xml = \simplexml_load_string($body);
                \libxml_disable_entity_loader($disabled);
                $this->parsedBody = $xml;
                break;
            default:
                break;
        }
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($name, $value)
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        $clone = clone $this;
        $clone->queryParams = $query;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withServerParams(array $server)
    {
        $clone = clone $this;
        $clone->serverParams = $server;
        return $clone;
    }

    /**
     * @param UploadedFileInterface[] $files
     */
    public static function assertUploadedFiles(array $files)
    {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFileInterface) {
                throw new \UnexpectedValueException(sprintf(
                    'Uploaded file must be an instance of Psr\\Http\\Message\\UploadedFileInterface; %s given',
                    \is_scalar($file) ? \gettype($file) : \get_class($file)
                ));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        self::assertUploadedFiles($uploadedFiles);
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        $clone = clone $this;
        $clone->parsedBody = $data;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($name)
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }
}
