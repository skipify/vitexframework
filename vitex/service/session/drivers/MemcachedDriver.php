<?php


namespace vitex\service\session\drivers;


use vitex\service\cache\CacheStore;
use vitex\service\cache\CacheUtil;
use vitex\service\session\SessionHandler;
use vitex\service\session\SessionDriverInterface;
use vitex\Vitex;

/**
 * Memcache缓存 MemcachedDriver
 * @package vitex\service\session\drivers
 */
class MemcachedDriver implements SessionDriverInterface
{
    const PREFIX = 'session_';

    /**
     * 会话生命周期(分钟)
     * @var int
     */
    private $lifetime;

    public function __construct()
    {
        $vitex = Vitex::getInstance();
        $this->lifetime = $vitex->getConfig('session')['lifetime'] * 60;
        CacheUtil::instance(CacheStore::MEMCACHED);
    }

    public function get($key)
    {
        if (!SessionHandler::isValidSessionId((string)$key)) {
            return null;
        }
        $key = self::PREFIX . $key;
        return CacheUtil::get($key);
    }

    public function set($key, $val, $expire = 60)
    {
        if (!SessionHandler::isValidSessionId((string)$key)) {
            return false;
        }
        $key = self::PREFIX . $key;
        return CacheUtil::set($key, $val, $expire);
    }

    public function delete($key)
    {
        if (!SessionHandler::isValidSessionId((string)$key)) {
            return false;
        }
        $key = self::PREFIX . $key;
        return CacheUtil::delete($key);
    }

    public function gc($maxlifetime)
    {
        return true;
    }


}