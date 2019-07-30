<?php

declare(strict_types=1);

namespace Async\Http;

/**
 * Class Cookie
 *
 * @package Async\Http
 */
class Cookie implements CookieInterface
{
    const EXPIRY_FORMAT = 'l, d-M-Y H:i:s T';

    /**
     * @var string|null
     */
    protected $domain;

    /**
     * @var \DateTimeInterface|null
     */
    protected $expiry;

    /**
     * @var bool
     */
    protected $httpOnly = false;

    /**
     * @var int
     */
    protected $maxAge = 0;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $path;

    /**
     * @var bool
     */
    protected $secure = false;

    /**
     * @var string|null
     */
    protected $value;

    /**
     * Cookie constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxAge()
    {
        return $this->maxAge;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     * {@inheritdoc}
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * {@inheritdoc}
     */
    public function withDomain($domain)
    {
        $clone = clone $this;
        $clone->domain = $domain;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withExpiry($expiry)
    {
        if (null !== $expiry) {
            self::assertCookieExpiry($expiry);
        }
        $clone = clone $this;
        $clone->expiry = self::normalizeCookieExpiry($expiry);
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withHttpOnly($flag)
    {
        $clone = clone $this;
        $clone->httpOnly = (bool)$flag;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withMaxAge($age)
    {
        $clone = clone $this;
        $clone->maxAge = (int)$age;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withName($name)
    {
        $clone = clone $this;
        $clone->name = $name;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path)
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withSecure($flag)
    {
        $clone = clone $this;
        $clone->secure = (bool)$flag;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withValue($value)
    {
        $clone = clone $this;
        $clone->value = $value;
        return $clone;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $params = array($this->name . '=' . \urlencode($this->value));
        if ($this->domain) {
            $params[] = "Domain={$this->domain}";
        }
        if ($this->expiry) {
            $params[] = "Expires=" . $this->expiry->format(self::EXPIRY_FORMAT);
        }
        if ($this->httpOnly) {
            $params[] = 'HttpOnly';
        }
        if ($this->maxAge > 0) {
            $params[] = "Max-Age={$this->maxAge}";
        }
        if ($this->path) {
            $params[] = "Path={$this->path}";
        }
        if ($this->secure) {
            $params[] = 'Secure';
        }
        return \implode('; ', $params);
    }

    /**
     * @param mixed $value
     */
    public static function assertCookieExpiry($value)
    {
        if (!($value instanceof \DateTime) && !\is_string($value) && !\is_int($value)) {
            throw new \InvalidArgumentException(
                "Cookie expiry must be string, int or an instance of \\DateTime; '%s' given",
                \is_object($value) ? \get_class($value) : \gettype($value)
            );
        }
    }

    /**
     * @param \DateTimeInterface|string|int|null $value
     * @return string
     */
    public static function normalizeCookieExpiry($value)
    {
        if (\is_string($value)) {
            $value = \DateTime::createFromFormat(Cookie::EXPIRY_FORMAT, $value);
        } elseif (\is_int($value)) {
            $value = \DateTime::createFromFormat('U', $value);
        }
        if ($value instanceof \DateTime) {
            return $value;
        }
    }

    /**
     * @param string $header
     * @return CookieInterface
     */
    public static function create($header)
    {
        $parts = \preg_split('~\\s*[;]\\s*~', $header);
        list($name, $value) = \explode('=', \array_shift($parts), 2);
        $cookie = new self($name);
        if (\is_string($value)) {
            $cookie = $cookie->withValue(\urldecode($value));
        }
        while ($nvp = \array_shift($parts)) {
            $nvp = \explode('=', $nvp, 2);
            $value = \count($nvp) === 2 ? $nvp[1] : null;
            switch (\strtolower($nvp[0])) {
                case 'domain':
                    $cookie = $cookie->withDomain($value);
                    break;
                case 'expires':
                    $cookie = $cookie->withExpiry($value);
                    break;
                case 'httponly':
                    $cookie = $cookie->withHttpOnly(true);
                    break;
                case 'max-age':
                    $cookie = $cookie->withMaxAge($value);
                    break;
                case 'path':
                    $cookie = $cookie->withPath($value);
                    break;
                case 'secure':
                    $cookie = $cookie->withSecure(true);
                    break;
            }
        }
        return $cookie;
    }
}
