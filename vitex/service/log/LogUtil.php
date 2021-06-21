<?php


namespace vitex\service\log;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use vitex\core\Exception;
use vitex\Vitex;

/**
 * 日志Util 提供快速的静态文件
 * @package vitex\service\log
 */
class LogUtil
{
    private static ?LoggerInterface $_instance = null;

    /**
     * 生成单例，但是如果重新执行instance方法  logger方法指定的话 会全局重置logger
     * @param null $logger
     * @return mixed|Logger
     */
    public static function instance($logger = null)
    {
        if ($logger !== null) {
            self::$_instance = $logger;
        }
        if (self::$_instance == null) {
            $vitex = Vitex::getInstance();
            $log = $vitex->getConfig('log');
            if ($log && $log instanceof LoggerInterface) {
                self::$_instance = $log;
            } else {
                self::$_instance = (new DefaultLogger())->getLogger();
            }
        }
        return self::$_instance;
    }


    public static function emergency($message, array $context = array())
    {
        if (self::$_instance == null) {
            throw new Exception("Please specify logger");
        }
        self::$_instance->emergency($message, $context);
    }

    public static function alert($message, array $context = array())
    {
        if (self::$_instance == null) {
            throw new Exception("Please specify logger");
        }
        self::$_instance->alert($message, $context);
    }

    public static function critical($message, array $context = array())
    {
        if (self::$_instance == null) {
            throw new Exception("Please specify logger");
        }
        self::$_instance->critical($message, $context);
    }

    public static function error($message, array $context = array())
    {
        if (self::$_instance == null) {
            throw new Exception("Please specify logger");
        }
        self::$_instance->error($message, $context);
    }

    public static function warning($message, array $context = array())
    {
        if (self::$_instance == null) {
            throw new Exception("Please specify logger");
        }
        self::$_instance->warning($message, $context);
    }

    public static function notice($message, array $context = array())
    {
        if (self::$_instance == null) {
            throw new Exception("Please specify logger");
        }
        self::$_instance->notice($message, $context);
    }

    public static function info($message, array $context = array())
    {
        if (self::$_instance == null) {
            throw new Exception("Please specify logger");
        }
        self::$_instance->info($message, $context);
    }

    public static function debug($message, array $context = array())
    {
        if (self::$_instance == null) {
            throw new Exception("Please specify logger");
        }
        self::$_instance->debug($message, $context);
    }

    public static function log($level, $message, array $context = array())
    {
        if (self::$_instance == null) {
            throw new Exception("Please specify logger");
        }
        self::$_instance->log($level, $message, $context);
    }
}