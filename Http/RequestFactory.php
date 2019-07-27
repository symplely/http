<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\Request;
use Async\Http\UriFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Class RequestFactory
 *
 * @package Async\Http
 */
class RequestFactory implements RequestFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (\is_string($uri)) {
            $factory = new UriFactory();
            $uri = $factory->createUri($uri);
        }
        return new Request($method, $uri);
    }
}
