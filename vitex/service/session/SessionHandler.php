<?php declare(strict_types=1);
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
/**
 * 会话管理基类
 */

namespace vitex\service\session;


use vitex\Vitex;

class SessionHandler implements \SessionHandlerInterface
{
    /**
     * session模型
     * @var SessionDriverInterface
     */
    private $driver;

    /**
     * session 存活时间  分钟
     * @var
     */
    private $lifetime;

    public function __construct(SessionDriverInterface $driver)
    {
        $vitex = Vitex::getInstance();
        $this->driver = $driver;
        $this->lifetime = $vitex->getConfig('session.lifetime');
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        return $this->driver->delete($session_id);
    }

    public function gc($maxlifetime)
    {
        return true;
    }

    public function open($save_path, $name)
    {
        return true;
    }

    public function read($session_id)
    {
        return $this->driver->get($session_id) ? (string)$this->driver->get($session_id) : '';
    }

    public function write($session_id, $session_data)
    {
        return $this->driver->set($session_id, $session_data, $this->lifetime * 60);
    }
}