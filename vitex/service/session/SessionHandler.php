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

    /**
     * 合法session id: 字母数字与逗号中划线，长度16-128
     * PHP内核仅校验可打印字符，../等路径穿越字符会被放行并原样传入save handler，
     * 驱动层若用id拼接文件/缓存key会造成任意文件读写，故在此统一白名单校验
     */
    private const ID_PATTERN = '/^[A-Za-z0-9,\-]{16,128}$/';

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

    /**
     * 校验session id是否安全合法
     */
    public static function isValidSessionId(string $session_id): bool
    {
        return preg_match(self::ID_PATTERN, $session_id) === 1;
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        //非法id直接视为无需销毁，不允许透传给驱动
        if (!self::isValidSessionId((string)$session_id)) {
            return true;
        }
        return (bool)$this->driver->delete($session_id);
    }

    public function gc($maxlifetime)
    {
        $this->driver->gc($maxlifetime);
        return 0;
    }

    public function open($save_path, $name)
    {
        return true;
    }

    public function read($session_id)
    {
        //非法id按无会话处理，不允许透传给驱动
        if (!self::isValidSessionId((string)$session_id)) {
            return '';
        }
        return $this->driver->get($session_id) ? (string)$this->driver->get($session_id) : '';
    }

    public function write($session_id, $session_data)
    {
        //非法id拒绝写入，防止路径穿越任意文件写入
        if (!self::isValidSessionId((string)$session_id)) {
            return false;
        }
        return (bool)$this->driver->set($session_id, $session_data, $this->lifetime * 60);
    }
}