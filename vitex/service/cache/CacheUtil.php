<?php
/**
 * vitex 缓存
 */

namespace vitex\service\cache;

use Doctrine\Common\Cache\CacheProvider;

/**
 * 缓存的util类 提供静态方法
 * CacheUtil::instance("xx")->get("key","default")
 * @package vitex\service\cache
 */
class CacheUtil
{
    /**
     * @var Cache
     */
    private static  $_instance;

    private function __construct()
    {

    }

    /**
     * 获取单例示例
     * @param string | CacheProvider $store
     * @return Cache
     */
    public static function instance(string | CacheProvider $store)
    {
        if (self::$_instance == null) {
            $cache = new Cache();
            self::$_instance = $cache;
        }
        self::$_instance->store($store);
        return self::$_instance;
    }

    /**
     * 直接获取缓存值
     * @param $key
     * @param null $default
     * @return mixed|null
     * @throws CacheException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function get($key, $default = null)
    {
        if (self::$_instance == null) {
            throw new CacheException("can't specify a cache store");
        }
        return self::$_instance->get($key, $default);
    }

    /**
     * 设置缓存值
     * @param $key
     * @param $value
     * @param null $ttl
     * @return bool|void
     * @throws CacheException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function set($key, $value, $ttl = null)
    {
        if (self::$_instance == null) {
            throw new CacheException("can't specify a cache store");
        }
        return self::$_instance->set($key, $value, $ttl);
    }

    /**
     * 删除缓存值
     * @param $key
     * @return bool|void
     * @throws CacheException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function delete($key)
    {
        if (self::$_instance == null) {
            throw new CacheException("can't specify a cache store");
        }
        return self::$_instance->delete($key);
    }

    /**
     * 清空缓存
     * @return bool
     * @throws CacheException
     */
    public static function clear()
    {
        if (self::$_instance == null) {
            throw new CacheException("can't specify a cache store");
        }
        return self::$_instance->clear();
    }

    /**
     * 获取缓存
     * @param iterable $keys
     * @param null $default
     * @return array|iterable|mixed[]
     * @throws InvalidArgumentException
     */
    public static function getMultiple($keys, $default = null)
    {
        if (self::$_instance == null) {
            throw new CacheException("can't specify a cache store");
        }
        return self::$_instance->getMultiple($keys, $default);
    }

    /**
     * 批量是设置缓存
     * @param $values
     * @param null $ttl
     * @return bool|void
     * @throws CacheException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function setMultiple($values, $ttl = null)
    {
        if (self::$_instance == null) {
            throw new CacheException("can't specify a cache store");
        }
        return self::$_instance->setMultiple($values, $ttl);
    }

    /**
     * 批量删除指定的缓存
     * @param $keys
     * @return bool
     * @throws CacheException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteMultiple($keys)
    {
        if (self::$_instance == null) {
            throw new CacheException("can't specify a cache store");
        }
        return self::$_instance->deleteMultiple($keys);
    }

    /**
     * 判断缓存是否存在
     * @param $key
     * @return bool
     * @throws CacheException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function has($key)
    {
        if (self::$_instance == null) {
            throw new CacheException("can't specify a cache store");
        }
        return self::$_instance->has($key);
    }

    /**
     * 获取元信息
     * @return array|null
     */
    public static function getStats()
    {
        if (self::$_instance == null) {
            throw new CacheException("can't specify a cache store");
        }
        return self::$_instance->getStats();
    }

}