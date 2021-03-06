<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\Stream;
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
     * List of valid HTTP protocol versions.
     *
     * Verified 2019-01-20
     *
     * @var string[]
     */
    protected $validProtocolVersions = [
        '1.0',
        '1.1',
        '2.0',
        '2',
        '3',
    ];

    /**
     * {@inheritDoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * {@inheritDoc}
     */
    public function withProtocolVersion($version): MessageInterface
    {
        $message = clone $this;

        $message->protocolVersion = $this->filterProtocolVersion($version);

        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * {@inheritDoc}
     */
    public function hasHeader($name): bool
    {
        return ($this->findHeaderKey($name) !== false);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeader($name): array
    {
        if (($key = $this->findHeaderKey($name)) !== false) {
            return (array) $this->headers[$key];
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderLine($name): string
    {
        return \implode(',', $this->getHeader($name));
    }

    /**
     * {@inheritDoc}
     */
    public function withHeader($name, $value): MessageInterface
    {
        $message = clone $this;
        $headers = [];

        foreach ($this->headers as $header => $values) {
            if (\strcasecmp($name, $header) === 0) {
                $headers[$name] = $value;

                continue;
            }

            $headers[$header] = $values;
        }

        $headers[$name] = $value;
        $message->headers = $this->filterHeaders($headers);

        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function withAddedHeader($name, $value): MessageInterface
    {
        if (($key = $this->findHeaderKey($name)) === false) {
            $key = $name;
        }

        $message = clone $this;
        $message->headers[$key][] = $value;

        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function withoutHeader($name): MessageInterface
    {
        if (($key = $this->findHeaderKey($name)) === false) {
            return $this;
        }

        $message = clone $this;
        unset($message->headers[$key]);

        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        if ($this->body === null) {
            return new Stream('');
        }

        return clone $this->body;
    }

    /**
     * {@inheritDoc}
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        $message = clone $this;

        $message->body = $this->filterBody($body);

        return $message;
    }

    /**
     * Find a header by its case-insensitive name.
     *
     * @param string $name
     * @param mixed $default
     * @return string|false
     */
    protected function findHeaderKey($name)
    {
        foreach ($this->headers as $key => $value) {
            if (\strtolower($name) === \strtolower($key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Filters body content to make sure it's valid.
     *
     * @param StreamInterface|resource|string $body
     *
     * @return StreamInterface
     */
    protected function filterBody($body): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            return clone $body;
        }

        return new Stream($body);
    }

    /**
     * Filters headers to make sure they're valid.
     *
     * @param string[] $headers
     *
     * @return array Array of string[]
     *
     * @throws \InvalidArgumentException
     */
    protected function filterHeaders(array $headers): array
    {
        foreach ($headers as $header => $values) {
            $this->validateHeader($header, $values);
            $headers[$header] = (array) $values;
        }

        return $headers;
    }

    /**
     * Filters protocol version to make sure it's valid.
     *
     * @param string $protocolVersion
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function filterProtocolVersion(string $protocolVersion): string
    {
        $version = \str_replace('HTTP/', '', \strtoupper($protocolVersion));

        if (!\in_array($version, $this->validProtocolVersions, true)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Invalid protocol version "%s". Valid versions are: ["%s"]',
                    $protocolVersion,
                    \implode('", "', $this->validProtocolVersions)
                )
            );
        }

        return $version;
    }

    /**
     * Validates a header name and values.
     *
     * @param string $header
     * @param string|string[] $values
     *
     * @throws \InvalidArgumentException
     */
    protected function validateHeader(string $header, $values = []): void
    {
        if (!\preg_match('@^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$@', $header)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Header "%s" contains invalid characters.',
                    $header
                )
            );
        }

        if (!\is_string($values) && !\is_array($values)) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(
                \sprintf(
                    'Values for header "%s" must be a string or array; %s given.',
                    $header,
                    \gettype($values)
                )
            );
            // @codeCoverageIgnoreEnd
        }

        foreach ((array) $values as $value) {
            if (!\is_string($value)) {
                // @codeCoverageIgnoreStart
                throw new \InvalidArgumentException(
                    \sprintf(
                        'Values for header "%s" must contain only strings; %s given.',
                        $header,
                        \gettype($value)
                    )
                );
                // @codeCoverageIgnoreEnd
            }

            if (
                \preg_match("~(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))~", $value)
                || \preg_match('~[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]~', $value)
            ) {
                throw new \InvalidArgumentException(
                    \sprintf(
                        'Header "%s" contains invalid value "%s".',
                        $header,
                        $value
                    )
                );
            }
        }
    }
}
