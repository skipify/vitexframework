<?php


namespace vitex\helper\attribute\parsedata;


use Doctrine\Common\Cache\ArrayCache;

/**
 * 缓存注解用于缓存数据的单例
 * Caching注解时会生成一个缓存数据，该数据会被缓存到此处
 * @package vitex\helper\attribute\parsedata
 */
class CacheData
{
    private ArrayCache $cache;

    private static $instance;

    private function __construct()
    {
        $this->cache = new ArrayCache();
    }

    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function save($key,$val){
        $this->cache->save($key,$val);
    }

    public function fetch($key){
        return $this->cache->fetch($key);
    }
}