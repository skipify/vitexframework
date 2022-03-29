<?php


namespace vitex\service\cache\config;


use vitex\service\config\ConfigInterface;

class RedisCacheConfig implements ConfigInterface
{


    private $instance = null;
    private string $host = '127.0.0.1';
    private int $port = 6379;
    private string $password = '';
    private int $databaseId = 0;

    /**
     * 哨兵
     * @var array
     */
    private mixed $sentinel = [];

    /**
     * 哨兵Master
     * @var string
     */
    private string $sentinelMaster = '';

    /**
     * 哨兵节点
     * @var array
     */
    private array $nodes = [];

    /**
     * 为了增加性能 增加一个哨兵主的缓存实现
     * driver =>  apcu/file
     * cacheName => "vitex" 存储名字,文件存储的话为一个文件路径，文件建议直接使用 php文件，缓存，会直接写入一个数组
     * expire => 10s 缓存有效期
     * @var array
     */
    private array $sentinelCache = [];

    /**
     * 超时时间
     * @var int
     */
    private int $timeout = 30;

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
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return int
     */
    public function getDatabaseId(): int
    {
        return $this->databaseId;
    }

    /**
     * @param int $databaseId
     */
    public function setDatabaseId(int $databaseId): void
    {
        $this->databaseId = $databaseId;
    }

    /**
     * @return array
     */
    public function getSentinel(): mixed
    {
        return $this->sentinel;
    }

    /**
     * @param array $sentinel
     */
    public function setSentinel(mixed $sentinel): void
    {
        $this->sentinel = $sentinel;
    }

    /**
     * @return string
     */
    public function getSentinelMaster(): string
    {
        return $this->sentinelMaster;
    }

    /**
     * @param string $sentinelMaster
     */
    public function setSentinelMaster(string $sentinelMaster): void
    {
        $this->sentinelMaster = $sentinelMaster;
    }

    /**
     * @return array
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @param array $nodes
     */
    public function setNodes(array $nodes): void
    {
        $this->nodes = $nodes;
    }

    /**
     * ['host' => '','port' => '']
     * @param array $node
     */
    public function addNode(array $node): void
    {
        $this->nodes[] = $node;
    }

    /**
     * @return array
     */
    public function getSentinelCache(): array
    {
        return $this->sentinelCache;
    }

    /**
     * @param array $sentinelCache
     */
    public function setSentinelCache(array $sentinelCache): void
    {
        $this->sentinelCache = $sentinelCache;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }



    public function toArray(): array
    {
        $config = [
            'instance' => $this->instance,
            'host' => $this->host,
            'port' => $this->port,
            'password' => $this->password,
            'databaseId' => $this->databaseId
        ];

        if ($this->getSentinelMaster()) {
            $config['sentinel'] = [
                'master' => $this->getSentinelMaster(),
                'nodes' => $this->nodes
            ];
        }
        return $config;
    }

    public static function fromArray(array $config): self
    {
        $instance = new self();
        foreach ($config as $key => $val) {
            if ($key == 'nodes') {
                if (count($val) == count($val, COUNT_RECURSIVE)) {
                    $instance->addNode($val);
                } else {
                    $instance->setNodes($val);
                }
            } else {
                $instance->{$key} = $val;
            }
        }
        return $instance;
    }
}