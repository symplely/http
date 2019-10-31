<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\ResponseSenderInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ResponseSender
 *
 * @package Async\Http
 */
class ResponseSender implements ResponseSenderInterface
{
    /**
     * {@inheritdoc}
     */
    public function send(ResponseInterface $response, $obl = null)
    {
        if (\headers_sent()) {
            throw new \RuntimeException('Not sending response as headers already sent');
        }
        if (\is_null($obl)) {
            $obl = \ob_get_level();
        }
        while (\ob_get_level() > $obl) {
            \ob_end_flush();
        }
        if (!$response->hasHeader('Content-Length') && \is_int($size = $response->getBody()->getSize())) {
            $response = $response->withHeader('Content-Length', (string) $size);
        }
        $this->sendStatusLine($response);
        $this->sendHeaders($response);
        $this->sendBody($response);
    }

    /**
     * @param ResponseInterface $response
     */
    protected function sendBody(ResponseInterface $response)
    {
        echo $response->getBody();
    }

    /**
     * @param ResponseInterface $response
     */
    protected function sendHeaders(ResponseInterface $response)
    {
        foreach ($response->getHeaders() as $header => $values) {
            $name = \str_replace(' ', '-', \ucwords(\str_replace('-', ' ', $header)));
            foreach ($values as $i => $value) {
                \header(\sprintf('%s: %s', $name, $value), $i === 0);
            }
        }
    }

    /**
     * @param ResponseInterface $response
     */
    protected function sendStatusLine(ResponseInterface $response)
    {
        \header(\trim(\sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        )));
    }
}
