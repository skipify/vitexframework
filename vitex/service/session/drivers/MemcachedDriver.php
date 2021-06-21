<?php


namespace vitex\service\session\drivers;


use vitex\service\cache\CacheStore;
use vitex\service\cache\CacheUtil;
use vitex\service\session\SessionDriverInterface;
use vitex\Vitex;

/**
 * Memcache缓存 MemcachedDriver
 * @package vitex\service\session\drivers
 */
class MemcachedDriver implements SessionDriverInterface
{
    const PREFIX = 'session_';

    public function __construct()
    {
        $vitex = Vitex::getInstance();
        $this->lifetime = $vitex->getConfig('session')['lifetime'] * 60;
        CacheUtil::instance(CacheStore::MEMCACHED);
    }

    public function get($key)
    {
        $key = self::PREFIX . $key;
        return CacheUtil::get($key);
    }

    public function set($key, $val, $expire = 60)
    {
        $key = self::PREFIX . $key;
        return CacheUtil::set($key, $val, $expire);
    }

    public function delete($key)
    {
        $key = self::PREFIX . $key;
        return CacheUtil::delete($key);
    }

    public function gc($maxlifetime)
    {
        return true;
    }


}