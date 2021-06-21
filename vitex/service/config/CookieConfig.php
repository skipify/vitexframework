<?php


namespace vitex\service\config;


class CookieConfig implements ConfigInterface
{
    /**
     *            'cookies.encrypt' => true, //是否启用cookie加密
     * 'cookies.lifetime' => '20 minutes',
     * 'cookies.path' => '/',
     * 'cookies.domain' => '',
     * 'cookies.secure' => false,
     * 'cookies.httponly' => false,
     * 'cookies.secret_key' => 'Vitex is a micro restfull framework',
     */

    private bool $encrypt = true;

    private string|int $lifetime = '20 minutes';

    private string $path = '/';

    private string $domain = '';

    private bool $secure = false;

    private bool $httponly = false;

    private string $secretKey = 'Vitex is a micro restfull framework';

    /**
     * @return bool
     */
    public function isEncrypt(): bool
    {
        return $this->encrypt;
    }

    /**
     * @param bool $encrypt
     */
    public function setEncrypt(bool $encrypt): void
    {
        $this->encrypt = $encrypt;
    }

    /**
     * @return int|string
     */
    public function getLifetime(): int
    {
        return is_string($this->lifetime) ? strtotime($this->lifetime) : $this->lifetime;
    }

    /**
     * @param int|string $lifetime
     */
    public function setLifetime(int|string $lifetime): void
    {
        $this->lifetime = is_string($lifetime) ? strtotime($lifetime) : $lifetime;
    }

    /**
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
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @param bool $secure
     */
    public function setSecure(bool $secure): void
    {
        $this->secure = $secure;
    }

    /**
     * @return bool
     */
    public function isHttponly(): bool
    {
        return $this->httponly;
    }

    /**
     * @param bool $httponly
     */
    public function setHttponly(bool $httponly): void
    {
        $this->httponly = $httponly;
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * @param string $secretKey
     */
    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }

//*            'cookies.encrypt' => true, //是否启用cookie加密
//* 'cookies.lifetime' => '20 minutes',
//* 'cookies.path' => '/',
//* 'cookies.domain' => '',
//* 'cookies.secure' => false,
//* 'cookies.httponly' => false,
//* 'cookies.secret_key' => 'Vitex is a micro restfull framework',

    public function toArray(): array
    {
        return [
            'encrypt' => $this->isEncrypt(),
            'lifetime' => $this->getLifetime(),
            'path' => $this->getPath(),
            'domain' => $this->getDomain(),
            'secure' => $this->isSecure(),
            'httponly' => $this->isHttponly(),
            'secret_key' => $this->getSecretKey()
        ];
    }

    public static function fromArray(array $config): self
    {
        $instance = new self();

        foreach ($config as $key => $val) {
            $key = str_replace('cookies.','',$key);
            if ($key == 'maxage') {
                $key = 'lifetime';
            }
            $instance->{$key} = $val;
        }
        return $instance;
    }
}