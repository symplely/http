<?php

namespace Async\Http;

use Async\Http\Stream;
use Async\Http\MessageAbstract;
use Async\Http\MessageValidations;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response
 * 
 * @package Async\Http
 */
class Response extends MessageAbstract implements ResponseInterface
{
    /**
     * HTTP reason phrase.
     *
     * @var string
     */
    protected $reasonPhrase = '';

    /**
     * HTTP status code.
     *
     * @var int
     */
    protected $statusCode;

    /**
     * Response constructor.
     * 
     * @param int $code
     * @param StreamInterface|resource|string $reasonPhrase
     */
    public function __construct(int $statusCode  = 200, $reasonPhrase = 'php://memory')
    {
        MessageValidations::assertStatusCode($statusCode);
        $this->statusCode = $statusCode;
        if ($reasonPhrase instanceof StreamInterface) {
            $this->body = $reasonPhrase;
        } elseif (\is_resource($reasonPhrase) || \is_string($reasonPhrase)) {
            $this->body = new Stream($reasonPhrase, 'wb+');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        MessageValidations::assertStatusCode($code);
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase;
        return $clone;
    }
}
