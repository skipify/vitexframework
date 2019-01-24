<?php declare(strict_types=1);

namespace vitex\service\log;

/**
 * 日志记录接口
 */

interface LogHandlerInterface
{
    /**
     * 写入日志
     * @param $level string 日志级别
     * @param $message string 日志信息
     * @return mixed
     */
    public function write($level,$message);
}