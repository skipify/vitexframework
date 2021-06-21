<?php


namespace vitex\service\amqp;


use PhpAmqpLib\Connection\AMQPStreamConnection;
use vitex\Vitex;

class AmqpUtil
{
    /**
     * 连接
     * @var $connection \AMQPConnection
     */
    private $connection;

    /**
     * 默认channel
     * @var $defaultChannel
     */
    private $defaultChannel;

    /**
     * 配置文件
     * @var AmqpConfig
     */
    private ?AmqpConfig $amqpConfig;

    /**
     * 存储多个实例
     * @var array
     */
    private static $_instances = [];

    /**
     * 加载配置
     * @param AmqpConfig|null $amqpConfig
     */
    private function __construct(?AmqpConfig $amqpConfig)
    {
        $this->amqpConfig = $amqpConfig;
    }

    public static function instance(?AmqpConfig $amqpConfig = null)
    {
        $instanceKey = $amqpConfig == null ? '_' : md5(serialize($amqpConfig->toArray()));
        $_instance = self::$_instances[$instanceKey] ?? null;
        if (!($_instance instanceof AmqpUtil)) {
            $_instance = new self($amqpConfig);
            self::$_instances[$instanceKey] = $_instance;
        }
        return $_instance;
    }

    /**
     * 连接服务器
     * @return mixed
     * @throws \AMQPConnectionException
     */
    public function connect()
    {
        if ($this->connection) {
            return $this->connection;
        }

        /**
         * 获取配置
         */
        if ($this->amqpConfig) {
            $configArr = $this->amqpConfig->toArray();
        } else {
            $vitex = Vitex::getInstance();
            $configArr = $vitex->getConfig('amqp');
        }

        $this->connection = new \AMQPConnection($configArr);

        if (!$this->connection->isConnected()) {
            $this->connection->connect();
        }
        return $this->connection;
    }

    /**
     * 默认的channel
     * @return Channel
     * @throws \AMQPConnectionException
     */
    public function defaultChannel()
    {
        if (!$this->connection) {
            $this->connect();
        }
        if (!$this->defaultChannel) {
            $this->defaultChannel = new Channel($this->connection);
        }
        return $this->defaultChannel;
    }

    /**
     * 生成一个默认的队列  绑定到默认交换机
     * @param string $routeKey
     * @param int $flag
     * @return Queue
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function defaultQueue(string $routeKey, int $flag = AMQP_NOPARAM)
    {
        /**
         * 默认队列名字
         */
        $defaultChannel = 'default';
        $queue = new Queue($this->defaultChannel());
        $queue->setName($defaultChannel);
        $queue->setFlags($flag);
        $queue->declareQueue();
        //当前队列绑定到默认交换机
        $queue->bind('', $routeKey);
        return $queue;
    }

    public function __destruct()
    {
        if ($this->connection) {
            $this->connection->disconnect();
        }
    }
}