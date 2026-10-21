<?php


namespace vitex\service\session\drivers;


use vitex\service\cache\CacheStore;
use vitex\service\cache\CacheUtil;
use vitex\service\session\SessionHandler;
use vitex\service\session\SessionDriverInterface;
use vitex\Vitex;

/**
 * Sqlite缓存
 * @package vitex\service\session\drivers
 */
class SqliteDriver implements SessionDriverInterface
{
    /**
     * 会话生命周期(分钟)
     * @var int
     */
    private $lifetime;

    public function __construct()
    {
        $vitex = Vitex::getInstance();
        $sessionConfig = $vitex->getConfig('session');
        $this->lifetime = $sessionConfig['lifetime'] * 60;
        CacheUtil::instance(CacheStore::SQLLITE3);
    }

    public function get($key)
    {
        if (!SessionHandler::isValidSessionId((string)$key)) {
            return null;
        }
        return CacheUtil::get($key);
    }

    public function set($key, $val, $expire = 60)
    {
        if (!SessionHandler::isValidSessionId((string)$key)) {
            return false;
        }
        return CacheUtil::set($key,$val,$expire);
    }

    public function delete($key)
    {
        if (!SessionHandler::isValidSessionId((string)$key)) {
            return false;
        }
        return CacheUtil::delete($key);
    }

    public function gc($maxlifetime)
    {
        return true;
    }

}