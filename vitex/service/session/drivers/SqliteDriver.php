<?php


namespace vitex\service\session\drivers;


use vitex\service\cache\CacheStore;
use vitex\service\cache\CacheUtil;
use vitex\service\session\SessionDriverInterface;
use vitex\Vitex;

/**
 * Sqlite缓存
 * @package vitex\service\session\drivers
 */
class SqliteDriver implements SessionDriverInterface
{
    public function __construct()
    {
        $vitex = Vitex::getInstance();
        $sessionConfig = $vitex->getConfig('session');
        $this->lifetime = $sessionConfig['lifetime'] * 60;
        CacheUtil::instance(CacheStore::SQLLITE3);
    }

    public function get($key)
    {
        return CacheUtil::get($key);
    }

    public function set($key, $val, $expire = 60)
    {
        return CacheUtil::set($key,$val,$expire);
    }

    public function delete($key)
    {
        return CacheUtil::delete($key);
    }

    public function gc($maxlifetime)
    {
        return true;
    }

}