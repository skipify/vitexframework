<?php


namespace vitex\service\session;

use vitex\service\config\ConfigInterface;

/**
 * Session配置
 * @package vitex\service\config
 */

class SessionConfig implements ConfigInterface
{
    private string $driver ='native';

    private int  $lifetime = 15;

    /**
     * 文件存储路径
     * @deprecated
     * @var string
     */
    private string $path = '';

    /**
     * 其他内容存储引擎的实例
     * @var mixed|null
     */
    private mixed $instance = null;

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * @param string $driver
     */
    public function setDriver(string $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * @return int|string
     */
    public function getLifetime(): int|string
    {
        return $this->lifetime;
    }

    /**
     * @param int $lifetime
     */
    public function setLifetime(int $lifetime): void
    {
        $this->lifetime = $lifetime;
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
     * @return mixed|null
     */
    public function getInstance(): mixed
    {
        return $this->instance;
    }

    /**
     * @param mixed|null $instance
     */
    public function setInstance(mixed $instance): void
    {
        $this->instance = $instance;
    }

    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'lifetime' => $this->lifetime,
            'path' => $this->path,
            'instance' => $this->instance
        ];
    }

    public static function fromArray(array $config): self
    {
        $instance = new self();

        foreach ($config as $key => $val) {
            $key = str_replace('session.','',$key);
            if ($key == 'maxage') {
                $key = 'lifetime';
            }
            $instance->{$key} = $val;
        }
        return $instance;
    }
}