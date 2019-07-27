<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\Stream;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Class StreamFactory
 *
 * @package Async\Http
 */
class StreamFactory implements StreamFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    /**
     * {@inheritDoc}
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return new Stream(fopen($filename, $mode));
    }
    /**
     * {@inheritDoc}
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
}
