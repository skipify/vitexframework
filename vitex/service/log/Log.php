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

namespace vitex\service\log;

use Psr\Log\LoggerInterface;

/**
 * 符合PSR-3标准的日志接口
 * @link( http://www.php-fig.org/psr/psr-3/, psr-3)
 */
class Log implements LoggerInterface
{
    /**
     * @var String log级别
     */
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    //
    protected $enabled;
    protected $level = 0;
    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * 日志的名字
     * @var
     */
    private $name;

    /**
     * 日志名称
     * @param string $name
     */
    public function __construct($name = '')
    {
        $this->name = $name;
        $this->enabled = true;
    }

    /**
     * 设置日志写入
     * @param $writer
     * @return $this
     */
    public function pushHandler($handler)
    {
        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * 获取当前的写入器
     * 支持多重日志写入
     * @return callable
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * 设置是否启用日志记录，如果不启用则自动进入黑洞
     * @param  \boolean $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (boolean) $enabled;
        return $this;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * System is unusable.
     *
     * @param  string $message
     * @param  array  $context
     * @return null
     */
    public function emergency($message, array $context = array())
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param  string $message
     * @param  array  $context
     * @return null
     */
    public function alert($message, array $context = array())
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param  string $message
     * @param  array  $context
     * @return null
     */
    public function critical($message, array $context = array())
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param  string $message
     * @param  array  $context
     * @return null
     */
    public function error($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param  string $message
     * @param  array  $context
     * @return null
     */
    public function warning($message, array $context = array())
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param  string $message
     * @param  array  $context
     * @return null
     */
    public function notice($message, array $context = array())
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param  string $message
     * @param  array  $context
     * @return null
     */
    public function info($message, array $context = array())
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param  string $message
     * @param  array  $context
     * @return null
     */
    public function debug($message, array $context = array())
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param  mixed  $level
     * @param  string $message
     * @param  array  $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if ($this->enabled && $this->handlers) {
            if (is_array($message) || (is_object($message) && !method_exists($message, '__toString'))) {
                $message = print_r($message, true);
            } else {
                $message = (string) $message;
            }
            //上下文信息处理
            if (count($context) > 0) {
                if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
                    $message .= ' - ' . $context['exception'];
                    unset($context['exception']);
                }
                $message = $this->interpolate($message, $context);
            }
            /**
             * @var $handler LogHandlerInterface
             */
            foreach ($this->handlers as $handler) {
                $handler->write($level, $message);
            }
        } else {
            return null;
        }
        return null;
    }

    /**
     * Interpolate log message
     * @param  mixed  $message The log message
     * @param  array  $context An array of placeholder values
     * @return string The processed string
     */
    protected function interpolate($message, $context = array())
    {
        $replace = array();
        foreach ($context as $key => $value) {
            $replace['{' . $key . '}'] = $value;
        }
        return strtr($message, $replace);
    }
}
