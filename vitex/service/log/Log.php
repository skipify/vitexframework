<?php

namespace vitex\service\log;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use vitex\Vitex;

/**
 * 日志
 * 可以自定义logger
 * @package vitex\service\log
 */
class Log implements LoggerInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct($logger = null)
    {
        if ($logger === null) {
            $vitex = Vitex::getInstance();
            $log = $vitex->getConfig('log');
            if ($log && $log instanceof LoggerInterface) {
                $this->logger = $log;
            } elseif($vitex->openLog){
                //使用默认logger记录
                $this->logger = (new DefaultLogger())->getLogger();
            } else {
                //不记录使用空logger记录
                $this->logger = new Logger("vitex");
            }
        } else {
            $this->logger = $logger;
        }
    }

    public function emergency($message, array $context = array())
    {
        $this->logger->emergency($message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->logger->alert($message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->logger->critical($message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->logger->error($message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->logger->warning($message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->logger->notice($message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->logger->info($message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->logger->debug($message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        $this->logger->log($level, $message, $context);
    }
}