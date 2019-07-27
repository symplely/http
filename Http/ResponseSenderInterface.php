<?php

declare(strict_types=1);

namespace Async\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface ResponseSenderInterface
 *
 * @package Async\Http
 */
interface ResponseSenderInterface
{
    /**
     * @param ResponseInterface $response
     * @param int $obl
     */
    public function send(ResponseInterface $response, $obl = null);
}
