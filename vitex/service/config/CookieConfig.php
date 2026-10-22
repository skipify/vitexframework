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
     * 'cookies.secret_key' => '请替换为私有随机密钥(禁止空值/框架公开默认值)',
     */

    private bool $encrypt = true;

    private string|int $lifetime = '20 minutes';

    private string $path = '/';

    private string $domain = '';

    private bool $secure = false;

    /**
     * 默认开启httponly防止XSS读取cookie
     * secure默认关闭，避免纯HTTP部署环境下cookie无法下发，生产环境HTTPS建议显式开启
     */
    private bool $httponly = true;

    /**
     * 禁止默认密钥：留空时启用加密的组件会直接抛出异常，强制配置私有随机密钥
     */
    private string $secretKey = '';

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

    /**
     * 历史版本硬编码的公开默认密钥，已知不安全
     */
    public const KNOWN_INSECURE_KEY = 'Vitex is a micro restfull framework';

    /**
     * 校验cookie加密密钥：禁止空值与框架公开默认密钥(fail-closed)，
     * 防止使用公开密钥的应用被离线伪造密文(会话/加密cookie身份伪造)
     * @param string $secretKey
     * @throws \InvalidArgumentException
     */
    public static function assertSecretKey(string $secretKey): void
    {
        if ($secretKey === '' || $secretKey === self::KNOWN_INSECURE_KEY) {
            throw new \InvalidArgumentException('cookie加密密钥未配置或使用了框架公开默认值，请配置独立的随机密钥(cookie.secret_key)');
        }
    }

//*            'cookies.encrypt' => true, //是否启用cookie加密
//* 'cookies.lifetime' => '20 minutes',
//* 'cookies.path' => '/',
//* 'cookies.domain' => '',
//* 'cookies.secure' => false,
//* 'cookies.httponly' => false,
//* 'cookies.secret_key' => '请替换为私有随机密钥(禁止空值/框架公开默认值)',

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
            //下划线配置键映射到驼峰属性，避免落到不存在的动态属性上导致配置静默失效
            if ($key == 'secret_key') {
                $key = 'secretKey';
            }
            if (!property_exists($instance, $key)) {
                throw new \InvalidArgumentException('未知的cookie配置项:' . $key);
            }
            $instance->{$key} = $val;
        }
        return $instance;
    }
}