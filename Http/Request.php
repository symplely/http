<?php

namespace Async\Http;

use Async\Http\MessageAbstract;
use Async\Http\MessageValidations;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Request
 * 
 * @package Async\Http
 */
class Request extends MessageAbstract implements RequestInterface
{
    /**
     * HTTP method being used, e.g. GET, POST, etc.
     *
     * @var string
     */
    protected $method;

    /**
     * HTTP request target.
     *
     * @var string
     */
    protected $requestTarget;

    /**
     * URI of the request.
     *
     * @var UriInterface
     */
    protected $uri;

    /**
     * Request constructor.
     * 
     * @param string $method
     * @param UriInterface $uri
     */
    public function __construct($method = 'GET', UriInterface $uri = null)
    {
        MessageValidations::assertMethod($method);
        $this->method = $method;
        $this->uri = $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }
        if (null !== $this->uri) {
            $target = $this->uri->getPath();
            if ($query = $this->uri->getQuery()) {
                $target .= "?{$query}";
            }
            if (!empty($target)) {
                return $target;
            }
        }
        return '/';
    }

    /**
     * {@inheritdoc}
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method)
    {
        MessageValidations::assertMethod($method);
        $clone = clone $this;
        $clone->method = $method;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget)
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone = clone $this;
        $clone->uri = $uri;
        if ($preserveHost) {
            if ($this->hasHeader('Host')) {
                return $clone;
            } elseif ($host = $uri->getHost()) {
                if ($port = $uri->getPort()) {
                    $host .= ":{$port}";
                }
                return $clone->withHeader('Host', $host);
            }
        }
        return $clone;
    }
}
