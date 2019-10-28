<?php

declare(strict_types=1);

namespace Async\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface SessionsInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * Sessions middleware which will automatically initialize session and write cookie to response.
     *
     * Sends the session headers in the Response, putting them under manual control rather than
     * relying on PHP to send them itself.
     *
     * Note that the Last-Modified value will not be the last time the session was
     * saved, but instead the current `time()`.
     *
     * @param ServerRequestInterface $request The HTTP request.
     *
     * @param ResponseInterface $response The HTTP response.
     *
     * @param RequestHandlerInterface|callable|null $middleware The next middleware to execute.
     *
     * @return ResponseInterface
     *
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $middleware = null);

    public function __destruct();

    public function start($id = null);
    /**
     * Save session data and end session.
     *
     * @param string $id
     */
    public function close($id = null);

    /**
     * Destroy session.
     */
    public function destroy();

    /**
     * Get session from request.
     *
     * @param ServerRequestInterface $request
     *
     * @return Sessions|null
     */
    public static function getSession(ServerRequestInterface $request);

    /**
     * @param array $data
     * @return void
     */
    public function fromArray(array $data): void;

    /**
     * Retrieve all of the session data.
     *
     * @return array
     */
    public function toArray() : array;

    /**
     * Retrieve a value from the session.
     *
     * @param mixed $default Default value to return if $name does not exist.
     * @return mixed
     */
    public function get(string $name, $default = null);

    /**
     * Set a key / value pair or array of key / value pairs in the session.
     *
     * @param string $name
     * @param mixed  $value
     * @return void
     */
    public function set(string $name, $value) : void;

    /**
     * Removes an item from the session.
     *
     * @param string $name
     * @return void
     */
    public function unset(string $name) : void;

    /**
     * Clears/Remove all of the items from the session.
     *
     * @return void
     */
    public function clear() : void;

    /**
     * Checks whether a given item exists in the session
     *
     * @param string $key
     * @return bool
     */
    public function has(string $name) : bool;

    /**
     * Generate a new session identifier for the session.
     *
     * @return void
     */
    public function regenerate(): void;

    /**
     * Gets the session id
     *
     * @return string
     */
    public function getId(): string;

    public function generateTokenFor(string $keyName = '__CSRF') : string;

    public function validateTokenFor(string $token, string $csrfKey = '__CSRF') : bool;

    public static function generateToken() : string;
}
