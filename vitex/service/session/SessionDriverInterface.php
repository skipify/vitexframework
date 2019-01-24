<?php declare(strict_types=1);
/**
 * 会话缓存接口
 * 如果是存储会话的介质连接自必须要实现下列三个方法
 * memcached
 * redis
 * mongo
 * ....
 * 实现此接口即可
 */

namespace vitex\service\session;


interface SessionDriverInterface
{
    /**
     * 获取缓存的内容
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * 设置缓存内容
     * @param $key
     * @param $val
     * @param $expire
     * @return mixed
     */
    public function set($key, $val, $expire = 60);

    /**
     * 删除一个缓存内容
     * @param $key
     * @return mixed
     */
    public function delete($key);
}