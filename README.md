# HTTP

[![build](https://github.com/symplely/http/actions/workflows/php.yml/badge.svg)](https://github.com/symplely/http/actions/workflows/php.yml)[![codecov](https://codecov.io/gh/symplely/http/branch/master/graph/badge.svg)](https://codecov.io/gh/symplely/http)[![Codacy Badge](https://api.codacy.com/project/badge/Grade/e18e2135abaf49f2b0fd9bf8d29c519d)](https://www.codacy.com/app/techno-express/http?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=symplely/http&amp;utm_campaign=Badge_Grade)[![Maintainability](https://api.codeclimate.com/v1/badges/eb6c23530d8c9f864f16/maintainability)](https://codeclimate.com/github/symplely/http/maintainability)

An complete [PSR-7](https://www.php-fig.org/psr/psr-7/) *Request*/*Response* implementation, with *Cookie*, and *Session* management/middleware.

## Installation

It's best to install using [Composer](https://getcomposer.org/).

```text
composer require symplely/http
```

## Outline

* [Abstract Messages](#abstract-messages)
* [Requests](#requests)
* [Responses](#responses)
* [File Uploads](#file-uploads)
* [Streams](#streams)
* [URIs](#uris)
* [Cookies](#cookies)
* [Sessions](#sessions)

## Abstract Messages

All `Request` and `Response` classes share a base `MessageAbstract` class that provides methods for interacting with the headers and body of a message.

The following methods are available on all `Request` and `Response` objects:

### `getProtocolVersion()`

Gets the HTTP protocol version as a string (e.g., "1.0" or "1.1").

### `withProtocolVersion($version)`

Returns a new instance of the message with the given HTTP protocol version as a string (e.g., "1.0" or "1.1").

### `getHeaders()`

Returns an array of the headers tied to the message. The array keys are the header names and each value is an array of strings for that header.

### `hasHeader($name)`

Makes a case-insensitive comparison to see if the header name given exists in the headers of the message. Returns `true` if found, `false` if not.

### `getHeader($name)`

Returns an array of strings for the values of the given case-insensitive header. If the header does not exist, it will return an empty array.

### `getHeaderLine($name)`

Returns a comma-separated string of all the values of the given case-insensitive header. If the header does not exist, it will return an empty string.

#### `withHeader($name, $value)`

Returns a new instance of the message while replacing the given header with the value or values specified.

```php
<?php

use Async\Http\ServerRequest;

$request = new ServerRequest(...);

$newRequest = $request->withHeader(
    'Content-Type',
    'text/html'
);

$newRequest = $request->withHeader(
    'Accept',
    ['application/json', 'application/xml']
);
```

### `withAddedHeader($name, $value)`

Returns a new instance of the message while adding the given header with the value or values specified. Very similar to `withHeader()`, except it maintains all existing headers.

### `withoutHeader($name)`

Returns a new instance of the message while completely removing the given header.

### `getBody()`

Gets the body of the message in a [`Psr\Http\Message\StreamInterface`](#streams) format.

### `withBody($body)`

Returns a new instance of the message using the given body. The body must be an instance of [`Psr\Http\Message\StreamInterface`](#streams).

## Requests

There are two types of requests: `Request` and `ServerRequest`. The `Request` class is used for outgoing requests, e.g. you send a request to another server. The `ServerRequest` class is used for incoming requests, e.g. someone makes a request to your website for you to process and respond to.

Unless you're building an HTTP client, you'll most likely only use the `ServerRequest`. Both are included because this library is a complete PSR-7 implementation.

* [Request](#request) (outgoing)
* [ServerRequest](#serverrequest) (incoming)

### `Request`

The `Request` class is used to build an outgoing, client-side request. Requests are considered immutable; all methods that change the state of the request return a new instance that contains the changes. The original request is always left unchanged.

### Building a `Request`

The static `Request::create` is the most consistent way to build a request.

```php
<?php

use Async\Http\Request;

$request = Request::create('GET', '/some/path?foo=bar');
```

Alternatively, you can build the request manually.

```php
<?php

use Async\Http\Request;

$method = 'GET';
$uri = 'http://example.com/';
$headers = ['Content-Type' => 'application/json'];
$body = '{"ping": "pong"}';
$protocolVersion = '1.1';

// All of the parameters are optional.
$request = new Request(
    $method,
    $uri,
    $headers,
    $body,
    $protocolVersion
);
```

In addition to all of the methods inherited from `MessageAbstract`, the following methods are available:

### `getRequestTarget()`

Gets the message's request target as it will be seen for clients. In most cases, this will be the origin-form of the URI, unless a specific value has been provided. For example, if you request "http://example.com/search?q=test" then this will contain "/search?q=test").

### `withRequestTarget($requestTarget)`

Returns a new instance with the message's request target, as given.

### `getMethod()`

Gets the HTTP method of the request.

### `withMethod($method)`

Returns a new instance with the message's HTTP method set as given. The method name should be uppercase, however it will not correct the capitalization for you.

### `getUri()`

Gets the URI of the request as a [`Psr\Http\Message\UriInterface`](#uris).

### `withUri($uri, $preserveHost = false)`

Returns a new instance with the message's URI set as given. It must be given a [`Psr\Http\Message\UriInterface`](#uris). If preserve host is set to `true`, it will not change the hostname of the request unless there isn't one already set.

## `ServerRequest`

The `ServerRequest` class extends `Request` and is used to build an incoming, server-side request. Requests are considered immutable; all methods that change the state of the request return a new instance that contains the changes. The original request is always left unchanged.

### Building a `ServerRequest`

The `ServerRequestFactory` is the most consistent way to build a request, regardless of the framework being used. All PSR-17 implementations share this method signature.

```php
<?php

use Async\Http\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

$factory = new ServerRequestFactory();

/** @var ServerRequestInterface */
$request = $factory->createServerRequest('GET', '/some/path?foo=bar');
$request = $factory->createServerRequest('GET', '/some/path?foo=bar', $serverParams);

```

In addition to all of the methods inherited from `Request`, the following methods are available:

### `getServerParams()`

Gets the server parameters for the request. Typically this is the contents of the `$_SERVER` variable, but doesn't have to be.

### `getCookieParams()`

Gets the cookie parameters for the request. The return structure matches the format of what `$_COOKIE` provides.

### `withCookieParams($cookies)`

Returns a new instance of the request with the updated cookie parameters. The `$cookies` parameter must match the structure that `$_COOKIE` provides.

### `getQueryParams()`

Gets the query string parameters for the request. Typically this is the contents of the `$_GET` variable, but doesn't have to be. It's also possible for the query parameters to be out of sync with the URI query parameters, as setting one does not automatically set the other.

### `withQueryParams($query)`

Returns a new instance of the request with the updated query parameters. Updating the query parameters will not automatically update the URI of the request.

### `getUploadedFiles()`

Gets an array of normalized file uploads where each node of the array is a [`Psr\Http\Message\UploadedFileInterface`](#uploaded-files).

### `withUploadedFiles($uploadedFiles)`

Returns a new instance of the request with the given file tree. Each node of the array must be a [`Psr\Http\Message\UploadedFileInterface`](#uploaded-files).

```php
<?php

use Async\Http\ServerRequest;

$request = new ServerRequest(...);

// A simple list.
$newRequest = $request->withUploadedFiles(
    [
        'fileA' => $fileA,
        'fileB' => $fileB,
    ]
);

// A nested list.
$newRequest = $request->withUploadedFiles(
    [
        'images' => [
            'small' => $fileA,
            'large' => $fileB,
        ],
        'foo' => [
            'bar' => [
                'baz' => $fileC,
            ],
        ],
    ]
);
```

### `getParsedBody()`

Gets the parameters of the request body. If the request Content-Type is either `application/x-www-form-urlencoded` or `multipart/form-data`, and the request method is `POST`, this method will return an array similar to `$_POST`. For other methods, such as `PUT` or `PATCH`, it will only parse the body if the Content-Type is `application/x-www-form-urlencoded` or `application/json` and then return the resulting array.

### `withParsedBody($body)`

Returns a new instance of the request with the given parsed body. It only accepts `array`, `object`, or `null` values.

### `getAttributes()`

Gets all custom attributes associated with the request. Attributes are application-specific data added to a request and can be anything, such as routing data or authentication flags.

### `getAttribute($name, $default = null)`

Gets the given attribute for the request. If the attribute is not set, the default value will be returned.

### `withAttribute($name, $value)`

Returns a new instance of the request with the given attribute set.

```php
<?php

use Async\Http\ServerRequest;

$request = new ServerRequest(...);

// If you have a route such as /product/{id}
// And a request for /product/123
// You can set the 'id' attribute to the product ID
$newRequest = $request->withAttribute('id', 123);

// Some controller for the route
$controller = function ($request) {
    // Look up product data
    $productId = $request->getAttribute('id');
    $product = $someRepository->find($productId);

    // Do something with $product
};

$controller($newRequest);
```

### `withoutAttribute($name)`

Returns a new instance of the request without the given attribute.

## Responses

There are three response classes available, mainly for convenience, but they all extend `Response`.

* [Response](#response)
* [JsonResponse](#jsonresponse)
* [RedirectResponse](#redirectresponse)

### `Response`

The `Response` class is used to return data to the client, typically in the form of HTML.

#### Building a `Response`

The static `Response::create` is the most consistent way to build a response.

```php
<?php

use Async\Http\Response;

$response = new Response();

/** @var ResponseInterface */
$response = Response::create();
$response = Response::create(404);
$response = Response::create(404, 'Not Found');

```

Or you can build one manually.

```php
<?php

use Async\Http\Response;

// Defaults to a 200 OK response.
$response = new Response('Hello, world!');

// Use a given status code.
$response = new Response(204);

// Send custom headers.
$response = new Response(
    302,
    'Goodbye, world!',
    ['Location' => '/bye-bye']
);
```

In addition to all of the methods inherited from `MessageAbstract`, the following methods are available:

##### `getStatusCode()`

Can be used to get the HTTP status code of the response (e.g., `200` or `404`).

##### `getReasonPhrase()`

Can be used to get the associated text for the status code (e.g., `OK` or `Not Found`).

##### `withStatus()`

Allows you to set the status and, optionally, the reason phrase of the response and returns the changes in a new response object.

```php
<?php

use Async\Http\Response;

$response = new Response(...);

$newResponse = $response->withStatus(204);
$newResponse = $response->withStatus(204, 'No Content');
```

### `JsonResponse`

The `JsonResponse` is a convenience extension of the `Response` class to make returning JSON data easier. It automatically encodes whatever data is given to it as JSON and sets the `Content-Type` header to `application/json`.

```php
<?php

use Async\Http\JsonResponse;

// Defaults to a 200 OK response.
$response = new JsonResponse(['message' => 'Hello, world!']);

// Custom 404 response.
$response = new JsonResponse(
    ['error' => 'Page not found'],
    404
);

// Include additional headers.
$response = new JsonResponse(
    ['error' => 'Invalid credentials'],
    401,
    ['X-Auth' => 'Failed']
);
```

### `RedirectResponse`

The `RedirectResponse` is a convenience extension of the `Response` class to make redirects easier. It automatically sets the `Location` header and includes a link in the body for the URI being redirected to.

```php
<?php

use Async\Http\RedirectResponse;

// Defaults to a 302 redirect.
$redirect = new RedirectResponse('/some/path');

// Use a given status code.
$redirect = new RedirectResponse('/some/path', 301);

// Send custom headers.
$redirect = new RedirectResponse(
    '/some/path',
    302,
    ['X-Message' => 'Bye-bye']
);
```

## File Uploads

The `UploadedFile` class attempts to fix issues with how PHP structures the `$_FILES` global.

### Building an `UploadedFile`

The static `UploadedFile::create` is the most consistent way to build an `UploadedFile`.

```php
<?php

use Async\Http\UploadedFile;

$stream = ...;

$file = UploadedFile::create($stream);
$file = UploadedFile::create($stream, $size, $error, $clientFilename, $clientMediaType);
```

The following methods are available:

#### `getStream()`

Gets a [`Psr\Http\Message\StreamInterface`](#streams) representing the file upload.

#### `moveTo($targetPath)`

Moves the file to the target path. Internally, this uses `move_uploaded_file()` or `rename()`, depending on whether it's called in a SAPI or non-SAPI environment.

#### `getSize()`

Gets the size of the file.

#### `getError()`

Gets any error codes associated to the file. This will return one of the [`UPLOAD_ERR_*` constants](http://php.net/manual/en/features.file-upload.errors.php).

#### `getClientFilename()`

Gets the filename sent by the client. The value of this should not be trusted, as it can easily be faked.

#### `getClientMediaType()`

Gets the media type sent by the client. The value of this should not be trusted, as it can easily be faked.

## Streams

Streams provide a standardized way of accessing streamable data, such as request/response bodies and file uploads. However, the might be useful in any other part of your code.

### Building a `Stream`

The static `Stream::create`, `Stream::createFromFile`, `Stream::createFromResource` is the most consistent way to build a `Stream`.

```php
<?php

use Async\Http\Stream;

$stream = Stream::create('string of data');
$stream = Stream::createFromFile('/path/to/file', 'r');

$resource = fopen('/path/to/file', 'wb+');
$stream = Stream::createFromResource($resource);
```

Alternatively, you can build a `Stream` manually:

```php
<?php

use Async\Http\Stream;

$stream = new Stream('string of data');
$stream = new Stream($resource);
```

The following methods are available:

#### `close()`

Closes the stream and any underlying resources.

#### `detach()`

Separates the underlying resource from the stream and returns it.

#### `getSize()`

Get the size of the stream, if known.

#### `tell()`

Returns the current position of the file pointer.

#### `eof()`

Returns true if the stream is at the end of the stream.

#### `isSeekable()`

Returns whether or not the stream is seekable.

#### `seek($offset, $whence = SEEK_SET)`

Seek to a position in the stream. `$whence` should be one of PHP' [`SEEK_*` constants](http://www.php.net/manual/en/function.fseek.php).

#### `rewind()`

Seek to the beginning of the stream.

#### `isWritable()`

Returns whether or not the stream is writable.

#### `write($string)`

Write data to the stream.

#### `isReadable()`

Returns whether or not the stream is readable.

#### `read($length)`

Read data from the stream.

#### `getContents()`

Returns the remaining contents of the stream.

#### `getMetadata($key = null)`

Get stream metadata as an associative array or retrieve a specific key. The keys returned are identical to the keys returned from PHP's [`stream_get_meta_data()`](http://php.net/manual/en/function.stream-get-meta-data.php) function.

## URIs

The `Uri` class makes working with URI values easier, as you can easily get or set only certain parts of the URI.

### Building a `Uri`

The static `Uri::create` is the most consistent way to build a `Uri`.

```php
<?php

use Async\Http\Uri;

$uri = Uri::create('/some/path?foo=bar');
$uri = Uri::create('https://example.com/search?q=test');
```

Alternatively, you can build a `Uri` manually:

```php
<?php

use Async\Http\Uri;

$uri = new Uri('/some/path?foo=bar');
$uri = new Uri('https://example.com/search?q=test');
```

The following methods are available:

#### `getScheme()`

Retrieve the scheme component of the URI.

#### `getAuthority()`

Retrieve the authority component of the URI. The authority syntax of the URI is `[user-info@]host[:port]`.

#### `getUserInfo()`

Retrieve the user information component of the URI. The syntax is `username[:password]`.

#### `getHost()`

Retrieve the host component of the URI.

#### `getPort()`

Retrieve the port component of the URI. If the port is a standard port (e.g., 80 for HTTP or 443 for HTTPS), this will return `null`.

#### `getPath()`

Retrieve the path component of the URI.

#### `getQuery()`

Retrieve the query string of the URI.

#### `getFragment()`

Retrieve the fragment component of the URI.

#### `withScheme($scheme)`

Returns a new instance with the specified scheme.

#### `withUserInfo($user, $password = null)`

Returns a new instance with the specified user information.

#### `withHost($host)`

Returns a new instance with the specified host.

#### `withPort($port)`

Returns a new instance with the specified port.

#### `withPath($path)`

Returns a new instance with the specified path.

#### `withQuery($query)`

Returns a new instance with the specified query.

#### `withFragment($fragment)`

Returns a new instance with the specified fragment.

## Cookies

Cookies handles two problems, managing `Cookie` Request headers and managing `Set-Cookie` Response headers. It does this by way of introducing a *Cookies* class to manage collections of **Cookie** instances and a *SetCookies* class to manage collections of **SetCookie** instances.

These classes are a merge and rework of repo [dflydev-fig-cookies
](https://github.com/dflydev/dflydev-fig-cookies).

Instantiating these collections looks like this:

```php
use Async\Http\Cookies;
use Async\Http\SetCookies;

// Get a collection representing the cookies in the `Cookie` headers
// of a PSR-7 Request.
$cookies = Cookies::fromRequest($request);

// Get a collection representing the cookies in the Set-Cookie headers
// of a PSR-7 Response
$setCookies = SetCookies::fromResponse($response);
```

After modifying these collections in some way, they are rendered into a PSR-7 Request or PSR-7 Response like this:

```php
// Put the `Cookie` headers and add them to the headers of a
// PSR-7 Request.
$request = $cookies->intoHeader($request);

// Put the `Set-Cookie` headers and add them to the headers of a
// PSR-7 Response.
$response = $setCookies->intoHeader($response);
```

For a simple `Cookie` instance, creation.

```php
use Async\Http\Cookie;

// Parse Set-Cookie header(s) and create an instance of CookieInterface.
$cookie = (new Cookie())
    ->create('PHPSESS=1234567890; Domain=domain.tld; Expires=Wed, 21 Oct 2015 07:28:00 GMT; HttpOnly; Max-Age=86400; Path=/admin; Secure');

// After making changes you can just cast it to a RFC-6265 valid string as show below.
$header = (string) $cookie;
```

Like PSR-7 Messages, `Cookie`, `Cookies`, `SetCookie`, and `SetCookies`
are all represented as immutable value objects and all mutations will
return new instances of the original with the requested changes.

While this style of design has many benefits it can become fairly
verbose very quickly. In order to get around that, the following provides
two facades in an attempt to help simply things and make the whole process
less verbose.

## Basic Usage

The easiest way to start working with Cookies is by using the
`RequestCookies` and `ResponseCookies` classes. They are facades to the
primitive Cookies classes. Their jobs are to make common cookie related
tasks easier and less verbose than working with the primitive classes directly.

There is overhead on creating `Cookies` and `SetCookies` and rebuilding
*requests* and *responses*. Each of these methods will go through this
process so be wary of using too many of these calls in the same section of
code. In some cases it may be better to work with the primitive classes
directly rather than using the facades.

### Request Cookies

Requests include cookie information in the **Cookie** request header. The
cookies in this header are represented by the `Cookie` class.

```php
use Async\Http\Cookie;

$cookie = Cookie::make('theme', 'blue');
```

To easily work with request cookies, use the `RequestCookies` facade.

#### Get a Request Cookie

The `get` method will return a `Cookie` instance. If no cookie by the specified
name exists, the returned `Cookie` instance will have a `null` value.

The optional third parameter to `get` sets the value that should be used if a
cookie does not exist.

```php
use Async\Http\RequestCookies;

$cookie = RequestCookies::get($request, 'theme');
$cookie = RequestCookies::get($request, 'theme', 'default-theme');
```

#### Set a Request Cookie

The `set` method will either add a cookie or replace an existing cookie.

The `Cookie` primitive is used as the second argument.

```php
use Async\Http\RequestCookies;

$request = RequestCookies::set($request, Cookie::make('theme', 'blue'));
```

#### Modify a Request Cookie

The `modify` method allows for replacing the contents of a cookie based on the
current cookie with the specified name. The third argument is a `callable` that
takes a `Cookie` instance as its first argument and is expected to return a
`Cookie` instance.

If no cookie by the specified name exists, a new `Cookie` instance with a
`null` value will be passed to the callable.

```php
use Async\Http\RequestCookies;

$modify = function (Cookie $cookie) {
    $value = $cookie->getValue();

    // ... inspect current $value and determine if $value should
    // change or if it can stay the same. in all cases, a cookie
    // should be returned from this callback...
    return $cookie->withValue($value);
}

$request = RequestCookies::modify($request, 'theme', $modify);
```

#### Remove a Request Cookie

The `remove` method removes a cookie if it exists.

```php
use Async\Http\RequestCookies;

$request = RequestCookies::remove($request, 'theme');
```

Note that this does not cause the client to remove the cookie. Take a look at
`ResponseCookies::expire` to do that.

### Response Cookies

Responses include cookie information in the **Set-Cookie** response header. The
cookies in these headers are represented by the `SetCookie` class.

```php
use Async\Http\SetCookie;

$setCookie = SetCookie::create('lu')
    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
    ->withExpires('Tue, 15-Jan-2013 21:47:38 GMT')
    ->withMaxAge(500)
    ->rememberForever()
    ->withPath('/')
    ->withDomain('.example.com')
    ->withSecure(true)
    ->withHttpOnly(true)
;
```

To easily work with response cookies, use the `ResponseCookies` facade.

#### Get a Response Cookie

The `get` method will return a `SetCookie` instance. If no cookie by the
specified name exists, the returned `SetCookie` instance will have a `null`
value.

The optional third parameter to `get` sets the value that should be used if a
cookie does not exist.

```php
use Async\Http\ResponseCookies;

$setCookie = ResponseCookies::get($response, 'theme');
$setCookie = ResponseCookies::get($response, 'theme', 'simple');
```

#### Set a Response Cookie

The `set` method will either add a cookie or replace an existing cookie.

The `SetCookie` primitive is used as the second argument.

```php
use Async\Http\ResponseCookies;

$response = ResponseCookies::set($response, SetCookie::create('token')
    ->withValue('a9s87dfz978a9')
    ->withDomain('example.com')
    ->withPath('/firewall')
);
```

#### Modify a Response Cookie

The `modify` method allows for replacing the contents of a cookie based on the
current cookie with the specified name. The third argument is a `callable` that
takes a `SetCookie` instance as its first argument and is expected to return a
`SetCookie` instance.

If no cookie by the specified name exists, a new `SetCookie` instance with a
`null` value will be passed to the callable.

```php
use Async\Http\ResponseCookies;

$modify = function (SetCookie $setCookie) {
    $value = $setCookie->getValue();

    // ... inspect current $value and determine if $value should
    // change or if it can stay the same. in all cases, a cookie
    // should be returned from this callback...

    return $setCookie
        ->withValue($newValue)
        ->withExpires($newExpires)
    ;
}

$response = ResponseCookies::modify($response, 'theme', $modify);
```

#### Remove a Response Cookie

The `remove` method removes a cookie from the response if it exists.

```php
use Async\Http\ResponseCookies;

$response = ResponseCookies::remove($response, 'theme');
```

#### Expire a Response Cookie

The `expire` method sets a cookie with an expiry date in the far past. This
causes the client to remove the cookie.

```php
use Async\Http\ResponseCookies;

$response = ResponseCookies::expire($response, 'session_cookie');
```

## Sessions

Normally, **PHP** will send out headers for you automatically when you call `session_start()`. However, this means the headers are not being sent as part of the PSR-7 response object, and are thus outside your control. `Sessions` puts them back under your control by placing the relevant headers in the PSR-7 response.

This class is a merge and rework of `Session`, and `SessionHeadersHandler` class in repo, [Relay.Middleware](https://github.com/relayphp/Relay.Middleware), and [sessionware
](https://github.com/juliangut/sessionware/tree/2.x).

This manager provides a nice OOP API to access session related actions:

`$session = new \Async\Http\Sessions($id, $cacheLimiter, $cacheExpire);`

When instantiating, you can pass a [cache limiter](http://php.net/session_cache_limiter) value as the second constructor parameter. The allowed values are 'nocache', 'public', 'private_no_cache', or 'private'. If you want no cache limiter header at all, pass an empty string ''. The default is 'nocache'.

You can also pass a [cache expire](http://php.net/session_cache_expire) value, in minutes, as the fourth constructor parameter. The default is 180 minutes.

* `Sessions::getSession($serverRequest)` get session instance from request
* `Sessions::start($id)` restart the session with id
* `Sessions::toArray()` retrieve all of the session data
* `Sessions::getId()` session identifier retrieval
* `Sessions::regenerate()` cryptographically secure session identifier regeneration
* `Sessions::has($item)` verify a variable is saved in session
* `Sessions::set($item, $val)` save a variable into session
* `Sessions::get($item, $default)` get a variable from session
* `Sessions::unset($item)` remove a variable from session
* `Sessions::clear()` remove all session variables
* `Sessions::close()` close session saving its contents, will also auto update $_SESSION on script shutdown, or __destruct.
* `Sessions::destroy()` destroy session and all its contents

_**Session Middleware**_

The same instance also comes with a middleware handler which you can use to automatically initialize session, and write session cookie to response.

```php
$session = new Sessions();

/**
 * Session is started, populated with default parameters and the response has session cookie header.
 *
 * @param ServerRequestInterface $request
 * @param ResponseInterface $response
 * @param RequestHandlerInterface|callable|null $next
 *
 * The callable should have something similar to this signature:
 * function (ServerRequestInterface $request, ResponseInterface $response) : Response {
 *  // your code
 * }
 */
$response = $session($request, $response, $next);
```

> **Never** make use of PHP built-in `session_*` functions (Session object would end up not being in sync) or `$_SESSION` global variable (changes will be ignored and overridden). **Use Sessions object API instead**

`Sessions` implements `IteratorAggregate`, `ArrayAccess`, `Countable`
So, it will look very much like `$_SESSION`.
Just replace the `$_SESSION` occurrences in your app with instance of the object.

### Write to session

```php
$session->abcd = 'efgh';
//or
$session['abcd'] = 'efgh';
//or
$session->set('abcd', 'efgh');
```

### Read from session

```php
$abcd =  $session->abc;
//or
$abcd = $session['abcd'];
//or
$abcd = $session->get('abcd');
```

### Remove from session

```php
unset($session->abc);
//or
unset($session['abcd']);
//or
$session->unset('abcd');
```

### Clear session data

```php
$session->clear();
```
