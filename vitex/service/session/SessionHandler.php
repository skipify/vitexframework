<?php declare(strict_types=1);
/**
 * Vitex 一个基于php8.0开发的 快速开发restful API的微型框架
 * @version  2.0.0
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


use vitex\service\session\drivers\FileDriver;
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

    public function __construct()
    {
        $vitex = Vitex::getInstance();
        /**
         * 读取配置文件的session配置
         */
        $sessionConfig = $vitex->getConfig('session');

        $driver = $sessionConfig['driver'];
        $driverClass = '\\vitex\\service\\session\drivers\\' . ucfirst($driver) . 'Driver';
        if (class_exists($driverClass)) {
            $driver = new $driverClass();
        } else {
            $driver = new FileDriver();
        }
        $this->driver = $driver;
        $this->lifetime = $sessionConfig['lifetime'];
    }

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $session_id): bool
    {
        return (bool)$this->driver->delete($session_id);
    }

    public function gc(int $maxlifetime): int|false
    {
        $this->driver->gc($maxlifetime);
        return 0;
    }

    public function open(string $save_path, string $name): bool
    {
        return true;
    }

    public function read(string $session_id): string|false
    {
        return $this->driver->get($session_id) ? (string)$this->driver->get($session_id) : '';
    }

    public function write(string $session_id, string $session_data): bool
    {
        return (bool)$this->driver->set($session_id, $session_data, $this->lifetime * 60);
    }
}