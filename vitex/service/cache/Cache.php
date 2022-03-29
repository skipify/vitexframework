<?php
/**
 * vitex 缓存service
 */

namespace vitex\service\cache;


use Doctrine\Common\Cache\CacheProvider;
use Psr\SimpleCache\CacheInterface;

/**
 * 缓存的统一封装
 * 配置文件中  cache  配置中配置多了 多个 不同的缓存引擎store  每个store都可以单独使用或者动态的更换
 *
 * @package vitex\service\cache
 */
class Cache implements CacheInterface
{
    /**
     * 使用了 连接实例连接
     */
    const INSTANCE_CONNECT = "instance_connect";
    /**
     * 缓存驱动
     * @var CacheProvider
     */
    private CacheProvider $cache;

    /**
     * 缓存类型
     * @var string
     */
    private string $store = "";

    public function __construct()
    {

    }

    /**
     * 设置缓存引擎，目前支持 doctrine的引擎，可以从 CacheStore 中取得常量
     * 可以指定一个存储引擎或者指定一个存储的类型
     * @param string | CacheProvider $store
     * @return $this
     */
    public function store(string|CacheProvider $store): Cache
    {
        if (is_string($store)) {
            $generateStore = new GenerateStore();
            $this->cache = $generateStore->get($store);
            $this->store = $store;
        } else {
            $this->cache = $store;
            $this->store = self::INSTANCE_CONNECT;
        }
        return $this;
    }

    /**
     * 获取当前的缓存类型
     * @return string
     */
    public function getCurrentStore(): string
    {
        return $this->store;
    }

    /**
     * 设置ID前缀
     * @param $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->cache->setNamespace($prefix);
        return $this;
    }

    public function get($key, $default = null)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException();
        }
        $val = $this->cache->fetch($key);
        return $val === false ? $default : $val;
    }

    public function set($key, $value, $ttl = null)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException();
        }
        return $this->cache->save($key, $value, $ttl);
    }

    public function delete($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException();
        }
        return $this->cache->delete($key);
    }

    public function clear()
    {
        return $this->cache->deleteAll();
    }

    /**
     * 获取缓存
     * @param iterable $keys
     * @param null $default
     * @return array|iterable|mixed[]
     * @throws InvalidArgumentException
     */
    public function getMultiple($keys, $default = null)
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException();
        }
        $values = $this->cache->fetchMultiple($keys);
        foreach ($keys as $key) {
            if (!isset($values[$key])) {
                $values[$key] = $default;
            }
        }
        return $values;
    }

    public function setMultiple($values, $ttl = null)
    {
        if (!is_array($values)) {
            throw new InvalidArgumentException();
        }
        return $this->cache->saveMultiple($values, $ttl);
    }


    public function deleteMultiple($keys)
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException();
        }
        return $this->cache->deleteMultiple($keys);
    }

    public function has($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException();
        }
        return $this->cache->contains($key);
    }

    /**
     * 获取元信息
     * @return array|null
     */
    public function getStats()
    {
        return $this->cache->getStats();
    }
}