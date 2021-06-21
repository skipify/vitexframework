<?php

namespace vitex\service\http;

use vitex\helper\traits\SetTrait;
use vitex\helper\Utils;
use vitex\service\config\CookieConfig;
use vitex\Vitex;

/**
 * Cookie的封装
 * @package vitex\service\http
 */
class Cookie implements \ArrayAccess
{

    /**
     * SameSite属性
     */
    const LAX = 'Lax';
    const STRICT = 'Strict';
    const NONE = 'None';

    private string $name;
    private string $value;
    private string $comment;
    private string $domain;
    private int $maxAge = -1;
    private string $path;
    private bool $secure;
    private bool $httpOnly;
    /**
     * 默认设置为LAX
     * @var string
     */
    private string $sameSite = self::LAX;

    private bool $needEncrypt;
    private string $secret;

    public function __construct(string $name, string $value)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Cookie name must be not empty');
        }
        $this->name = $name;
        $vitex = Vitex::getInstance();
        $cookieConfig = CookieConfig::fromArray($vitex->getConfig('cookie'));

        $this->setMaxAge($cookieConfig->getLifetime());
        $this->setPath($cookieConfig->getPath());
        $this->setDomain($cookieConfig->getDomain());
        $this->setSecure($cookieConfig->isSecure());
        $this->setHttpOnly($cookieConfig->isHttponly());

        $this->secret = $cookieConfig->getSecretKey();
        $this->needEncrypt = $cookieConfig->isEncrypt();

        if ($this->needEncrypt) {
            $this->value = Utils::encrypt($value, $this->secret);
        } else {
            $this->value = $value;
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Cookie name must be not empty');
        }
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        if ($this->needEncrypt) {
            return Utils::decrypt($this->value, $this->secret);
        } else {
            return $this->value;
        }
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        if ($this->needEncrypt) {
            $this->value = Utils::encrypt($value, $this->secret);
        } else {
            $this->value = $value;
        }
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * The (sub)domain that the cookie is available to.
     * Setting this to a subdomain (such as 'www.example.com') will make the cookie available
     * to that subdomain and all other sub-domains of it (i.e. w2.www.example.com).
     * To make the cookie available to the whole domain (including all subdomains of it),
     * simply set the value to the domain name ('example.com', in this case).
     * Older browsers still implementing the deprecated » RFC 2109 may require a leading . to match all subdomains.
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return int
     */
    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    /**
     * The time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch.
     * In other words, you'll most likely set this with the time() function plus the number of seconds before you want it to expire.
     * Or you might use mktime(). time()+60*60*24*30 will set the cookie to expire in 30 days.
     * If set to 0, or omitted, the cookie will expire at the end of the session (when the browser closes).
     * eg. time() + 1000
     * @param int $maxAge
     */
    public function setMaxAge(int $maxAge): void
    {
        $this->maxAge = $maxAge;
    }

    /**
     * The path on the server in which the cookie will be available on.
     * If set to '/', the cookie will be available within the entire domain.
     * If set to '/foo/', the cookie will only be available within the /foo/ directory and all sub-directories such as /foo/bar/ of domain.
     * The default value is the current directory that the cookie is being set in.
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * When set to true, the cookie will only be set if a secure connection exists.
     * On the server-side, it's on the programmer to send this kind of cookie only on secure connection (e.g. with respect to $_SERVER["HTTPS"]).
     * @param bool $secure
     */
    public function setSecure(bool $secure): void
    {
        $this->secure = $secure;
    }

    /**
     * @return bool
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * When true the cookie will be made accessible only through the HTTP protocol.
     * This means that the cookie won't be accessible by scripting languages, such as JavaScript.
     * It has been suggested that this setting can effectively help to reduce identity theft through XSS
     * attacks (although it is not supported by all browsers), but that claim is often disputed. true or false
     * @param bool $httpOnly
     */
    public function setHttpOnly(bool $httpOnly): void
    {
        $this->httpOnly = $httpOnly;
    }

    /**
     * @return string
     */
    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * The value of the samesite element should be either None, Lax or Strict.
     * If any of the allowed options are not given,
     * their default values are the same as the default values of the explicit parameters.
     * If the samesite element is omitted, `Lax` is set
     * @param string $sameSite
     */
    public function setSameSite(string $sameSite): void
    {
        $this->sameSite = $sameSite;
    }

    /**
     * 发送 cookie 到客户端
     */
    public function send()
    {
        $options = [
            'expires' => $this->getMaxAge(),
            'path' => $this->getPath(),
            'domain' => $this->getDomain(),
            'secure' => $this->isSecure(),     // or false
            'httponly' => $this->isHttpOnly(),    // or false
            'samesite' => $this->getSameSite() // None || Lax  || Strict
        ];

        \setcookie(
            $this->getName(), $this->getValue(),
            $options
        );
    }

    public function offsetExists($offset)
    {
        if (in_array($offset, ['comment', 'domain', 'maxAge', 'path', 'secure', 'httpOnly'])) {
            return true;
        }
        return false;
    }

    public function offsetGet($offset)
    {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset)
    {
        $this->{$offset} = null;
    }

    public function __toString()
    {
        return json_encode([
            "name" => $this->getName(),
            "value" => $this->getValue(),
            'expires' => $this->getMaxAge(),
            'path' => $this->getPath(),
            'domain' => $this->getDomain(),
            'secure' => $this->isSecure(),     // or false
            'httponly' => $this->isHttpOnly(),    // or false
            'samesite' => $this->getSameSite() // None || Lax  || Strict
        ], JSON_UNESCAPED_SLASHES);
    }
}