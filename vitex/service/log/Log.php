<?php

namespace vitex\service\log;

use Monolog\Logger;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use vitex\Vitex;

/**
 * 日志
 * 可以自定义logger
 * @package vitex\service\log
 */
class Log extends AbstractLogger
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
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

    /**
     * @inheritdoc
     */
    public function log(mixed $level, string|\Stringable $message, array $context = array()): void
    {
        $this->logger->log($level, $message, $context);
    }
}