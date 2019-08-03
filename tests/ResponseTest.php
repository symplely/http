<?php

namespace Async\Tests;

use Async\Http\Stream;
use Async\Http\Response;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    /**
     * @var Response
     */
    private $fixture = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = new Response();
    }

    public function testInstanceOf(): void
    {
        self::assertInstanceOf(ResponseInterface::class, $this->fixture);
    }

    /**
     * @param int $code
     * @param string $reason
     * @param string $expected
     *
     * @dataProvider sampleStatus
     */
    public function testWithStatusCodeOnly(int $code, string $reason, string $expected): void
    {
        $clone = $this->fixture->withStatus($code, $reason);

        $oldCode   = $this->fixture->getStatusCode();
        $oldReason = $this->fixture->getReasonPhrase();
        $newCode   = $clone->getStatusCode();
        $newReason = $clone->getReasonPhrase();

        self::assertNotSame($this->fixture, $clone);
        self::assertEquals($this->fixture::STATUS_OK, $oldCode);
        self::assertEquals('OK', $oldReason);
        self::assertEquals($code, $newCode);
        self::assertEquals($expected, $newReason);
    }

    public function sampleStatus(): array
    {
        $validStatusCodes = $this->getValidStatusCodes();

        $data = [];
        foreach ($validStatusCodes as $code => $reason) {
            $data['default '.$code] = [
                'code' => $code,
                'reason' => '',
                'expected' => $reason,
            ];
        }

        $data['reason override'] = [
            'code' => Response::STATUS_FOUND,
            'reason' => 'Somewhat Found',
            'expected' => 'Somewhat Found',
        ];

        return $data;
    }

    public function testWithStatusThrowsException(): void
    {
        $code = $this->getInvalidStatusCode();

        $message = 'HTTP status code "'.$code.'" is invalid.';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->fixture->withStatus($code);
    }

    public function testBody(): void
    {
        $body = uniqid();

        $fixture = new Response(Response::STATUS_OK, $body);

        self::assertEquals($body, (string) $fixture->getBody());
    }

    public function testStatus(): void
    {
        $codes  = $this->getValidStatusCodes();
        $code   = intval(array_rand($codes));
        $reason = $codes[$code];

        $fixture = new Response($code, '');

        self::assertEquals($code, $fixture->getStatusCode());
        self::assertEquals($reason, $fixture->getReasonPhrase());
    }

    public function testInvalidStatusCodeThrowsException(): void
    {
        $code = $this->getInvalidStatusCode();

        $message = 'HTTP status code "'.$code.'" is invalid.';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new Response($code, '');
    }

    public function testHeaders(): void
    {
        $headerA = uniqid('header');
        $headerB = uniqid('header');
        $valueA  = uniqid('value');
        $valueB  = uniqid('value');

        $fixture = new Response(200, '', [$headerA => $valueA, $headerB => [$valueB]]);

        $actual   = $fixture->getHeaders();
        $expected = [$headerA => [$valueA], $headerB => [$valueB]];

        self::assertEquals($expected, $actual);
    }

    public function testCreateResponseDefault(): void
    {
        $actual = $this->fixture->create();

        self::assertInstanceOf(ResponseInterface::class, $actual);
        self::assertEquals(200, $actual->getStatusCode());
        self::assertEquals('OK', $actual->getReasonPhrase());
    }

    /**
     * @param int $code
     * @param string $reason
     * @param string $expected
     *
     * @dataProvider sampleStatus
     */
    public function testCreateResponse(int $code, string $reason, string $expected): void
    {
        $actual = $this->fixture->create($code, $reason);

        self::assertInstanceOf(ResponseInterface::class, $actual);
        self::assertEquals($code, $actual->getStatusCode());
        self::assertEquals($expected, $actual->getReasonPhrase());
    }

    /**
     * Gets a list of valid status codes and reasons.
     *
     * @return string[]
     */
    private function getValidStatusCodes(): array
    {
        return Response::REASON_PHRASES;
    }

    /**
     * Gets an invalid status code.
     *
     * @return int
     */
    private function getInvalidStatusCode()
    {
        $validStatusCodes = $this->getValidStatusCodes();

        do {
            $code = rand(1, 999);
        } while (isset($validStatusCodes[$code]));

        return $code;
    }
}
