<?php


namespace vitex\service\cache\config;


use vitex\service\config\ConfigInterface;

/**
 * Memcache配置
 * @package vitex\service\cache\config
 */
class MemcacheCacheConfig implements ConfigInterface
{
    private $instance = null; //实例 或者下方的 host/port
    private string $host = '127.0.0.1';
    private int $port = 11211;

    /**
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param mixed $instance
     */
    public function setInstance($instance): void
    {
        $this->instance = $instance;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
    }


    public function toArray(): array
    {
        return [
            'instance' => $this->instance,
            'host' => $this->host,
            'port' => $this->port
        ];
    }

    public static function fromArray(array $config): self
    {
        $instance = new self();
        foreach ($config as $Key => $val) {
            $instance->{$key} = $val;
        }
        return $instance;
    }

}