<?php

namespace Async\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class MessageAbstract
 * 
 * @package Async\Http
 */
abstract class MessageAbstract implements MessageInterface
{
    /**
     * HTTP response body.
     *
     * @var StreamInterface
     */
    protected $body;

    /**
     * @var array
     */
    protected $headerNames = array();

    /**
     * HTTP headers.
     *
     * @var array
     */
    protected $headers = array();

    /**
     * HTTP protocol version.
     *
     * @var string
     */
    protected $protocolVersion = '1.1';
	
    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name)
    {
        if ($this->hasHeader($name)) {
            $name = $this->headerNames[\strtolower($name)];
            return $this->headers[$name];
        }
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name)
    {
        $values = $this->getHeader($name);
        if (\count($values)) {
            return \implode(',', $values);
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name): bool
    {
        return \array_key_exists(\strtolower($name), $this->headerNames);
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value): MessageInterface
    {
        MessageValidations::assertHeaderName($name);
        if ($this->hasHeader($name)) {
            $name = $this->headerNames[\strtolower($name)];
            $value = \is_array($value) ? $value : array($value);
            \array_walk($value, array(__NAMESPACE__ . '\\MessageValidations', 'assertHeaderValue'));
            $clone = clone $this;
            $clone->headers[$name] += \array_merge($clone->headers[$name], $value);
            return $clone;
        } else {
            return $this->withHeader($name, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value): MessageInterface
    {
        MessageValidations::assertHeaderName($name);
        $value = \is_array($value) ? $value : array($value);
        \array_walk($value, array(__NAMESPACE__ . '\\MessageValidations', 'assertHeaderValue'));
        $clone = clone $this;
        $clone->headerNames[\strtolower($name)] = $name;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name): MessageInterface
    {
        $clone = clone $this;
        if ($this->hasHeader($name)) {
            unset($clone->headers[$name], $clone->headerNames[\strtolower($name)]);
        }
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version): MessageInterface
    {
        MessageValidations::assertProtocolVersion($version);
        $clone = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }
}
