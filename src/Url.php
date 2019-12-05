<?php

namespace HughCube\PUrl;

use HughCube\PUrl\Exceptions\InvalidArgumentException;
use Psr\Http\Message\UriInterface;

class Url implements UriInterface
{
    /**
     * @var int[]
     */
    private $schemes = [
        'http'  => 80,
        'https' => 443,
    ];

    /**
     * @var string|null url scheme
     */
    private $scheme;

    /**
     * @var string|null url host
     */
    private $host;

    /**
     * @var int|null url port
     */
    private $port;

    /**
     * @var string|null url user
     */
    private $user;

    /**
     * @var string|null url pass
     */
    private $pass;

    /**
     * @var string|null url path
     */
    private $path;

    /**
     * @var string|null url query string
     */
    private $query;

    /**
     * @var string|null url fragment
     */
    private $fragment;

    /**
     * 获取实例.
     *
     * @param null|UriInterface $url
     *
     * @return static
     */
    public static function instance($url = null)
    {
        return new static($url);
    }

    /**
     * Url constructor.
     *
     * @param null|string|string[]|UriInterface $url
     */
    final protected function __construct($url = null)
    {
        if ($url instanceof UriInterface) {
            $this->parsePsrUrl($url);
        } elseif (is_string($url)) {
            $this->parseStringUrl($url);
        } elseif (is_array($url)) {
            $this->parseArrayUrl($url);
        }
    }

    /**
     * 解析 Psr 标准库的url.
     *
     * @param UriInterface $url
     *
     * @return $this
     */
    private function parsePsrUrl(UriInterface $url)
    {
        $this->scheme = empty($scheme = $url->getScheme()) ? null : $scheme;
        $this->host = empty($host = $url->getHost()) ? null : $host;
        $this->port = empty($port = $url->getPort()) ? null : $port;
        $this->path = empty($path = $url->getPath()) ? null : $path;
        $this->query = empty($query = $url->getQuery()) ? null : $query;
        $this->fragment = empty($fragment = $url->getFragment()) ? null : $fragment;

        $user = $this->getUserInfo();
        $user = explode(':', $user);
        $this->user = (is_array($user) && isset($user[0])) ? $user[0] : null;
        $this->pass = (is_array($user) && isset($user[1])) ? $user[1] : null;

        return $this;
    }

    /**
     * 解析字符串url.
     *
     * @param string $url
     *
     * @return $this
     */
    private function parseStringUrl($url)
    {
        if (!static::isUrlString($url)) {
            throw new InvalidArgumentException('the parameter must be a url');
        }

        /** @var string[] $parts */
        $parts = parse_url($url);
        $this->parseArrayUrl($parts);

        return $this;
    }

    /**
     * 解析数组url.
     *
     * @param string[]|int[] $parts
     *
     * @return $this
     */
    private function parseArrayUrl(array $parts)
    {
        $this->scheme = isset($parts['scheme']) ? $parts['scheme'] : null;
        $this->host = isset($parts['host']) ? $parts['host'] : null;
        $this->port = isset($parts['port']) ? $parts['port'] : null;
        $this->user = isset($parts['user']) ? $parts['user'] : null;
        $this->pass = isset($parts['pass']) ? $parts['pass'] : null;
        $this->path = isset($parts['path']) ? $parts['path'] : null;
        $this->query = isset($parts['query']) ? $parts['query'] : null;
        $this->fragment = isset($parts['fragment']) ? $parts['fragment'] : null;

        return $this;
    }

    /**
     * 填充 Psr 标准库的url.
     *
     * @param UriInterface $url
     *
     * @return UriInterface
     */
    public function fillPsrUri(UriInterface $url)
    {
        return $url->withScheme($this->getScheme())
            ->withUserInfo($this->getUser(), $this->getPass())
            ->withHost($this->getHost())
            ->withPort($this->getPort())
            ->withPath($this->getPath())
            ->withQuery($this->getQuery())
            ->withFragment($this->getFragment());
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {
        return strval($this->scheme);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority()
    {
        $authority = $host = $this->getHost();
        if (empty($host)) {
            return $authority;
        }

        $userInfo = $this->getUserInfo();
        if (!empty($userInfo)) {
            $authority = "{$userInfo}@{$authority}";
        }

        $port = $this->getPort();
        if ($this->isNonStandardPort() && !empty($port)) {
            $authority = "{$authority}:{$port}";
        }

        return $authority;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo()
    {
        $userInfo = $user = $this->getUser();
        if (empty($user)) {
            return $userInfo;
        }

        $pass = $this->getPass();
        if (!empty($pass)) {
            $userInfo = "{$userInfo}:{$pass}";
        }

        return $userInfo;
    }

    /**
     * 获取 url user.
     *
     * @return string
     */
    public function getUser()
    {
        return strval($this->user);
    }

    /**
     * 获取 url pass.
     *
     * @return string
     */
    public function getPass()
    {
        return strval($this->pass);
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return strval($this->host);
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        if (!empty($this->port)) {
            return $this->port;
        }

        $scheme = $this->getScheme();
        if (empty($scheme)) {
            return;
        }

        return isset($this->schemes[$scheme]) ? $this->schemes[$scheme] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        if (empty($this->path)) {
            return '';
        }

        return '/' === substr($this->path, 0, 1) ? $this->path : "/{$this->path}";
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return strval($this->query);
    }

    /**
     * 获取query数组.
     *
     * @return array
     */
    public function getQueryArray()
    {
        $query = $this->getQuery();

        $queryArray = [];
        if (!empty($query)) {
            parse_str($query, $queryArray);
        }

        return is_array($queryArray) ? $queryArray : [];
    }

    /**
     * 是否存在query的key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasQueryKey($key)
    {
        $queryArray = $this->getQueryArray();

        return array_key_exists($key, $queryArray);
    }

    /**
     * 是否存在query的key.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return array|string
     */
    public function getQueryValue($key, $default = null)
    {
        $queryArray = $this->getQueryArray();

        return array_key_exists($key, $queryArray) ? $queryArray[$key] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment()
    {
        return strval($this->fragment);
    }

    /**
     * Return the string representation as a URI reference.
     *
     * @return string
     */
    public function toString()
    {
        $url = '';

        $scheme = $this->getScheme();
        if (!empty($scheme)) {
            $url = "{$scheme}://{$url}";
        }

        $authority = $this->getAuthority();
        if (!empty($authority)) {
            $url = "{$url}{$authority}";
        }

        $path = $this->getPath();
        if (!empty($path)) {
            $url = "{$url}{$path}";
        }

        $query = $this->getQuery();
        if (!empty($query)) {
            $url = "{$url}?{$query}";
        }

        $fragment = $this->getFragment();
        if (!empty($fragment)) {
            $url = "{$url}#{$fragment}";
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme($scheme)
    {
        $new = clone $this;
        $new->scheme = $scheme;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null)
    {
        $new = clone $this;
        $new->user = $user;
        $new->pass = $password;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withHost($host)
    {
        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withPort($port)
    {
        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path)
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery($query)
    {
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * Return an instance with the specified query array.
     *
     * @param array $queryArray
     *
     * @return static
     */
    public function withQueryArray(array $queryArray)
    {
        return $this->withQuery(http_build_query($queryArray));
    }

    /**
     * Create a new URI with a specific query string value removed.
     *
     * @param string|int $key
     *
     * @return static
     */
    public function withoutQueryValue($key)
    {
        $queryArray = $this->getQueryArray();

        if (isset($queryArray[$key])) {
            unset($queryArray[$key]);
        }

        return $this->withQueryArray($queryArray);
    }

    /**
     * Create a new URI with a specific query string value.
     *
     * @param string     $key
     * @param string|int $value
     *
     * @return static
     */
    public function withQueryValue($key, $value)
    {
        $queryArray = $this->getQueryArray();
        $queryArray[$key] = $value;

        return $this->withQueryArray($queryArray);
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment)
    {
        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Is a given port non-standard for the current scheme?
     *
     * @return bool
     */
    private function isNonStandardPort()
    {
        if (!$this->scheme && $this->port) {
            return true;
        }

        if (!$this->host || !$this->port) {
            return false;
        }

        return !isset($this->schemes[$this->scheme])
            || $this->port !== $this->schemes[$this->scheme];
    }

    /**
     * is url string.
     *
     * @param mixed $url
     *
     * @return bool
     */
    public static function isUrlString($url)
    {
        return false !== filter_var($url, FILTER_VALIDATE_URL);
    }
}
