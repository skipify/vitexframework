<?php
/**
 * vitex 缓存service
 */

namespace vitex\service\cache;

use vitex\core\Exception;

/**
 * 哨兵模式连接 redis
 * @package vitex\service\cache
 */
class RedisSentinel
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var float 超时时间
     */
    protected $timeout;

    /**
     * 哨兵集合
     * @var array
     */
    protected $sentinels = [];

    /**
     * @var RedisSentinel
     */
    protected $currentSentinel = null;

    /**
     * 链接密码
     * @var string
     */
    protected $password = '';

    /**
     * @var int $databaseId
     */
    protected $databaseId = 0;

    public function __construct(float $timeout = null)
    {
        $this->redis = new \Redis();
        $this->timeout = $timeout === null ? 0.0 : $timeout;
    }

    public function __destruct()
    {
        try {
            $this->redis->close();
        } catch (\Exception $e) {
        }
    }

    /**
     * @param string $host
     * @param int $port
     * @param null $password
     * @param null $databaseId
     * @return bool
     */
    public function connect(string $host, int $port = 26379, $password = null, $databaseId = null)
    {
        try {
            $this->redis->connect($host, $port, $this->timeout);
        } catch (\RedisException $e) {
            throw new CacheException("Cant Connect host:{$host} port:{$port}");
        }

        if ($password) {
            $this->password = $password;
        }
        if ($databaseId) {
            $this->databaseId = $databaseId;
        }
        return true;
    }

    /**
     * 认证
     * @param string $password
     * @return $this
     */
    public function auth(string $password)
    {
        if ($password) {
            $this->redis->auth($password);
        }
        return $this;
    }

    /**
     * 设置 databaseId
     * @param int $index
     * @return $this
     */
    public function select(int $index)
    {
        $this->redis->select($index);
        return $this;
    }

    /**
     * 添加一个哨兵
     * @param string $host
     * @param int $port
     * @param null $password
     * @param null $databaseId
     * @return $this
     */
    public function addSentinel(string $host, int $port, $password = null, $databaseId = null)
    {
        $this->sentinels[] = [
            'host' => $host,
            'port' => $port,
            'password' => $password,
            'databaseId' => $databaseId
        ];
        return $this;
    }

    /**
     * 根据masterName获得redis实例
     *
     * @param $masterName
     * @param array $cacheConfig 哨兵缓存配置
     * @return \Redis
     * @throws \RedisException
     */
    public function getRedis($masterName)
    {
        $address = $this->getMasterAddrByNameFromPoll($masterName);
        $redis = new \Redis();
        if (!$redis->connect($address['ip'], $address['port'], $this->currentSentinel->getTimeout())) {
            throw new \RedisException("connect to redis failed");
        }
        return $redis;
    }

    /**
     * 获得当前哨兵
     * @return RedisSentinel|null
     */
    public function getCurrentSentinel()
    {
        return $this->currentSentinel;
    }

    /**
     * Pong 返回
     *
     * @return string  异常为返回失败
     */
    public function ping()
    {
        return $this->redis->ping();
    }

    /**
     * 返回masters 和他们的装填
     *
     * @return array
     */
    public function masters()
    {
        return $this->parseArrayResult($this->redis->rawCommand('SENTINEL', 'masters'));
    }

    /**
     * 解析redis返回值
     *
     * @param array $data
     * @return array
     */
    private function parseArrayResult(array $data)
    {
        $result = array();
        $count = count($data);
        for ($i = 0; $i < $count;) {
            $record = $data[$i];
            if (is_array($record)) {
                $result[] = $this->parseArrayResult($record);
                $i++;
            } else {
                $result[$record] = $data[$i + 1];
                $i += 2;
            }
        }

        return $result;
    }

    /**
     * 返回指定的master状态和信息
     *
     * @param string $masterName
     * @return array
     */
    public function master($masterName)
    {
        return $this->parseArrayResult($this->redis->rawCommand('SENTINEL', 'master', $masterName));
    }

    /**
     * 返回slaves和信息装填
     *
     * @param string $masterName
     * @return array
     */
    public function slaves($masterName)
    {
        return $this->parseArrayResult($this->redis->rawCommand('SENTINEL', 'slaves', $masterName));
    }

    /**
     * 返回哨兵示例和他们的装填
     *
     * @param string $masterName
     * @return array
     */
    public function sentinels($masterName)
    {
        return $this->parseArrayResult($this->redis->rawCommand('SENTINEL', 'sentinels', $masterName));
    }

    /**
     * 根据名称返回IP和PORT
     *
     * @param string $masterName
     * @return array
     */
    public function getMasterAddrByName($masterName)
    {
        $data = $this->redis->rawCommand('SENTINEL', 'get-master-addr-by-name', $masterName);
        return array(
            'ip' => $data[0],
            'port' => $data[1]
        );
    }

    /**
     * 从哨兵池中获得master
     * @param $masterName
     */
    public function getMasterAddrByNameFromPoll($masterName)
    {
        /**
         * @var $sentinel RedisSentinel
         */
        foreach ($this->sentinels as $sentinelConfig) {
            try {
                $sentinel = new RedisSentinel();
                if (!$sentinel->connect($sentinelConfig['host'], $sentinelConfig['port'], $sentinelConfig['password'], $sentinelConfig['databaseId'])) {
                    continue;
                }
                $this->currentSentinel = $sentinel;
                $data = $sentinel->getMasterAddrByName($masterName);
                $data['time'] = time();
                return $data;
            } catch (\Exception $e) {
                continue;
            }
        }
        throw new Exception("All Sentinels fail");
    }

    /**
     * 根据pattern 重置所有的master
     *
     * @param string $pattern
     * @return int
     */
    public function reset($pattern)
    {
        return $this->redis->rawCommand('SENTINEL', 'reset', $pattern);
    }

    /**
     * 标记失败
     *
     * @param string $masterName
     * @return boolean
     */
    public function failOver($masterName)
    {
        return $this->redis->rawCommand('SENTINEL', 'failover', $masterName) === 'OK';
    }

    /**
     * @param string $masterName
     * @return boolean
     */
    public function ckquorum($masterName)
    {
        return $this->checkQuorum($masterName);
    }

    /**
     * Check if the current Sentinel configuration is able to
     * reach the quorum needed to failover a master, and the majority
     * needed to authorize the failover. This command should be
     * used in monitoring systems to check if a Sentinel deployment is ok.
     *
     * @param string $masterName
     * @return boolean
     */
    public function checkQuorum($masterName)
    {
        return $this->redis->rawCommand('SENTINEL', 'ckquorum', $masterName);
    }

    /**
     * Force Sentinel to rewrite its configuration on disk,
     * including the current Sentinel state. Normally Sentinel rewrites
     * the configuration every time something changes in its state
     * (in the context of the subset of the state which is persisted on disk across restart).
     * However sometimes it is possible that the configuration file is lost because of
     * operation errors, disk failures, package upgrade scripts or configuration managers.
     * In those cases a way to to force Sentinel to rewrite the configuration file is handy.
     * This command works even if the previous configuration file is completely missing.
     *
     * @return boolean
     */
    public function flushConfig()
    {
        return $this->redis->rawCommand('SENTINEL', 'flushconfig');
    }

    /**
     * This command tells the Sentinel to start monitoring a new master with the specified name,
     * ip, port, and quorum. It is identical to the sentinel monitor configuration directive
     * in sentinel.conf configuration file, with the difference that you can't use an hostname in as ip,
     * but you need to provide an IPv4 or IPv6 address.
     *
     * @param $masterName
     * @param $ip
     * @param $port
     * @param $quorum
     * @return boolean
     */
    public function monitor($masterName, $ip, $port, $quorum)
    {
        return $this->redis->rawCommand('SENTINEL', 'monitor', $masterName, $ip, $port, $quorum);
    }

    /**
     * is used in order to remove the specified master: the master will no longer be monitored,
     * and will totally be removed from the internal state of the Sentinel,
     * so it will no longer listed by SENTINEL masters and so forth.
     *
     * @param $masterName
     * @return boolean
     */
    public function remove($masterName)
    {
        return $this->redis->rawCommand('SENTINEL', 'remove', $masterName);
    }

    /**
     * The SET command is very similar to the CONFIG SET command of Redis,
     * and is used in order to change configuration parameters of a specific master.
     * Multiple option / value pairs can be specified (or none at all).
     * All the configuration parameters that can be configured via sentinel.conf
     * are also configurable using the SET command.
     *
     * @param $masterName
     * @param $option
     * @param $value
     * @return boolean
     */
    public function set($masterName, $option, $value)
    {
        return $this->redis->rawCommand('SENTINEL', 'set', $masterName, $option, $value);
    }

    /**
     * get last error
     *
     * @return string
     */
    public function getLastError()
    {
        return $this->redis->getLastError();
    }

    /**
     * clear last error
     *
     * @return boolean
     */
    public function clearLastError()
    {
        return $this->redis->clearLastError();
    }

    /**
     * sentinel server info
     *
     * @return string
     */
    public function info()
    {
        return $this->redis->info();
    }

    /**
     * @return float
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

}