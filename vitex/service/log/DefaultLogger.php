<?php


namespace vitex\service\log;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * 默认的缓存配置
 * @package vitex\service\log
 */
class DefaultLogger
{

    const LOG_FILE = "vitex-runtime.log";

    /**
     * 日志格式
     */
    const FORMATTER = "%datetime% > %level_name% > %message% %context% %extra%\n";

    /**
     * 默认时间格式
     */
    const DATE_FORMAT = "Y-m-d H:i:s";

    /**
     * 格式
     * @return LineFormatter
     */
    private function getFormatter()
    {
        return new LineFormatter(self::FORMATTER, self::DATE_FORMAT);
    }

    /**
     * 获取默认的log
     * @return Logger
     */
    public function getLogger()
    {
        $logger = new Logger("vitex");
        $logHandler = new StreamHandler(sys_get_temp_dir() . '/' . self::LOG_FILE);
        $logHandler->setFormatter($this->getFormatter());
        $logger->pushHandler($logHandler);
        return $logger;
    }
}