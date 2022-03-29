<?php
/**
 * vitex 缓存service
 */

namespace vitex\service\cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\MongoDBCache;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Cache\PredisCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\SQLite3Cache;
use Doctrine\Common\Cache\VoidCache;
use MongoDB\Driver\Manager;
use MongoDB\Collection;
use vitex\core\Exception;
use vitex\Vitex;

/**
 * 根据不同的缓存引擎传递不同的引擎配置参数
 * @package vitex\service\cache
 */
class GenerateStore
{
    const EXTENSION_PHP = '.vitex.php';
    const EXTENSION_FILE = '.vitex.data';


    /**
     * 缓存引擎的命名空间
     */
    const CACHE_STORE_NAMESPACE = "\Doctrine\Common\Cache\\";

    /**
     * @param string $store
     * @return CacheProvider
     * @throws InvalidArgumentException
     */
    public function get(string $store): CacheProvider
    {
        $class = self::CACHE_STORE_NAMESPACE . $store . 'Cache';
        if (!class_exists($class)) {
            throw new InvalidArgumentException("不存在的缓存引擎");
        }
        switch ($store) {
            case CacheStore::APCU:
            case CacheStore::ARR:
            case CacheStore::VOID:
            case CacheStore::WIN_CACHE:
            case CacheStore::ZEND_DATA:
                return new $class();
            case CacheStore::FILE:
                return $this->getFile();
            case CacheStore::PHP_FILE:
                return $this->getPhpFile();
            case CacheStore::REDIS:
                return $this->getRedis();
            case CacheStore::PREDIS:
                return $this->getPredis();
            case CacheStore::MONGODB:
                return $this->getMongoDB();
            case CacheStore::MEMCACHED:
                return $this->getMemcached();
            case CacheStore::SQLLITE3:
                return $this->getSQLite3();
            default:
                return new VoidCache();
        }
    }

    /**
     * 缓存的配置为  cache 键值
     * @param $store
     */
    private function getConfig($store)
    {
        $vitex = Vitex::getInstance();
        $cacheConfig = $vitex->getConfig('cache');
        if (!$cacheConfig) {
            throw new Exception("NOT FOUND CACHE CONFIG ,Please add `cache` key in your config file; ");

        }
        return $cacheConfig[$store] ?? null;
    }

    /**
     * 配置为
     * ["path" => "/path/log"]
     * @return CacheProvider|null
     */
    private function getFile()
    {
        $config = $this->getConfig(CacheStore::FILE);
        if ($config === null) {
            throw new Exception("FILE CACHE 未配置");
        }
        return new FilesystemCache($config['path'], self::EXTENSION_FILE);
    }

    /**
     * 配置为 配置路径
     * ['path' => '/path/log']
     * @return CacheProvider|null
     */
    private function getPhpFile()
    {
        $config = $this->getConfig(CacheStore::PHP_FILE);
        if ($config === null) {
            throw new Exception("PHPFILE CACHE 未配置");

        }
        return new PhpFileCache($config['path'], self::EXTENSION_PHP);
    }

    /**
     * 会调用 redis缓存引擎配置如下
     * [
     *    'instance' => \Redis, //redis实例
     *    'host' => '',
     *    'port' => 123,
     *    'password' => '',
     *    'databaseId' => 0,
     *    'sentinel' => [
     *        'master' => 'T1',
     *        'nodes' => [
     *             [
     *                  "host" => '192.168.0.1',
     *                  'port' => 17001
     *              ],
     *             [
     *                  "host" => '192.168.0.2',
     *                  'port' => 17002
     *              ],
     *               [
     *                  "host" => '192.168.0.3',
     *                  'port' => 17003
     *              ],
     *        ]
     *     ]
     * ]
     * @return CacheProvider|null
     */
    private function getRedis()
    {
        $config = $this->getConfig(CacheStore::REDIS);
        if ($config === null) {
            throw new Exception("Redis CACHE 未配置");

        }

        if (isset($config['instance'])) {
            $redis = $config['instance'];
        } elseif (isset($config['sentinel'])) {
            //哨兵模式
            $redisSentinel = new RedisSentinel();
            foreach ($config['sentinel']['nodes'] as $node) {
                $redisSentinel->addSentinel($node['host'], $node['port']);
            }
            $redis = $redisSentinel->getRedis($config['sentinel']['master'], $config['sentinel']['cache'] ?? []);

            if ($config['password']) {
                $redis->auth($config['password']);
            }

            if ($config['databaseId']) {
                $redis->select($config['databaseId']);
            }

        } else {
            //单机模式
            $redis = new \Redis();
            $redis->connect($config['host'], $config['port']);
            if (!empty($config['password'])) {
                $redis->auth($config['password']);
            }
            if (!empty($config['databaseId'])) {
                $redis->select(intval($config['databaseId']));
            }
        }

        $redisCache = new RedisCache();
        $redisCache->setRedis($redis);
        return $redisCache;
    }

    /**
     * [
     *    'instance' => instance
     * ]
     * @return CacheProvider|null
     */
    private function getPredis()
    {
        $config = $this->getConfig(CacheStore::REDIS);
        if ($config === null || empty($config['instance'])) {
            throw new Exception("Predis CACHE 未配置");

        }
        return new PredisCache($config['instance']);
    }

    /**
     *  [
     *   'instance' => 示例, //
     *   'host' => '',
     *    'port' => 123
     * ]
     * @return CacheProvider|null
     */
    private function getMemcached()
    {
        $config = $this->getConfig(CacheStore::MEMCACHED);
        if ($config === null) {
            throw new Exception("MEMCACHED CACHE 未配置");
        }
        if (isset($config['instance'])) {
            $memcached = $config['instance'];
        } else {
            $memcached = new \Memcached();
            $memcached->addServer($config['host'], $config['port']);
        }
        $memcache = new MemcachedCache();
        $memcache->setMemcached($memcached);
        return $memcache;
    }

    /**
     * [
     *   'instance' => 示例,
     *   'host' => '',
     *   'port' => 123,
     *   'database' => '',
     *   'collection' => ''
     * ]
     * @return CacheProvider|null
     */
    private function getMongoDB()
    {
        $config = $this->getConfig(CacheStore::MONGODB);
        if ($config === null) {
            throw new Exception("MONGODB CACHE 未配置");
        }
        if (isset($config['instance'])) {
            $mongoDB = $config['instance'];
        } else {
            $manager = new Manager("mongodb://{$config['host']}:{$config['port']}");
            $mongoDB = new Collection($manager, $config['database'], $config['collection']);
        }
        return new MongoDBCache($mongoDB);
    }

    /**
     * [
     *   'db' => '',
     *   'table' => ''
     * ]
     * @return CacheProvider|null
     */
    private function getSQLite3()
    {
        $config = $this->getConfig(CacheStore::SQLLITE3);
        if ($config === null) {
            throw new Exception("SQLITE CACHE 未配置");
        }

        $sqlite = new \SQLite3($config['db']);
        return new SQLite3Cache($sqlite, $config['table']);
    }
}