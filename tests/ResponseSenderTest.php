<?php

namespace Async\Tests;

use Async\Http\Response;
use Async\Http\ResponseSender;
use PHPUnit\Framework\TestCase;

class ResponseSenderTest extends TestCase
{
    protected $response;

    protected function setUp(): void
    {
        $response = new Response();
        $response = $response->withStatus(404, 'Not Found')
            ->withHeader('content-type', 'text/plain')
            ->withHeader('X-Powered-By', 'PHP/7.1');
        $response->getBody()->write('This URL does not exist.');
        $this->response = $response;
    }

    public function testHeadersSent()
    {
        $sender = new ResponseSender();
        $this->expectException(\RuntimeException::class);
        $sender->send($this->response);
    }

    /**
     * @runInSeparateProcess
     */
    public function testBody()
    {
        $sender = new ResponseSender();
        $this->expectOutputString('This URL does not exist.');
        $sender->send($this->response);
    }
}
