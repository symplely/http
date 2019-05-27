<?php

namespace Async\Tests;

use Async\Http\ResponseFactory;
use PHPUnit\Framework\TestCase;

class ResponseFactoryTest extends TestCase
{
    public function testStatusCode()
    {
        $factory = new ResponseFactory();
        $this->assertEquals(200, $factory->createResponse()->getStatusCode());
        $this->assertEquals(404, $factory->createResponse(404)->getStatusCode());
    }
}
