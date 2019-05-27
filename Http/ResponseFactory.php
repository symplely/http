<?php

namespace Async\Http;

use Async\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * Class ResponseFactory
 * 
 * @package Async\Http
 */
class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createResponse(int $code = 200, string $reasonPhrase = 'php://memory'): ResponseInterface
    {
        return new Response($code, $reasonPhrase);
    }
}
