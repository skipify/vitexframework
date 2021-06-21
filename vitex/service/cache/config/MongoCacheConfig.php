<?php


namespace vitex\service\cache\config;


use vitex\service\config\ConfigInterface;

class MongoCacheConfig implements ConfigInterface
{
    private $instance = null; //实例或者下方的配置
    private string $host = '127.0.0.1';
    private int $port = 27017;
    private string $database = ''; //数据库
    private string $collection = '';

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

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @param string $database
     */
    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

    /**
     * @return string
     */
    public function getCollection(): string
    {
        return $this->collection;
    }

    /**
     * @param string $collection
     */
    public function setCollection(string $collection): void
    {
        $this->collection = $collection;
    }

    public function toArray(): array
    {
        return [
            'instance' => $this->instance,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'collection' => $this->collection
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