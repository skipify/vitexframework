<?php declare(strict_types=1);

namespace vitex\service\log\handlers;

use vitex\service\log\LogHandlerInterface;
use vitex\Vitex;

/**
 * 标准的输出到屏幕
 */
class Std implements LogHandlerInterface
{
    /**
     * 输出日志的格式  html /string
     * @var
     */
    private $format;

    public function __construct($format = '')
    {
        if (!$format) {
            $vitex = Vitex::getInstance();
            $format = $vitex->getConfig('log.format');
        }
        $this->format = $format;
    }

    public function write($level, $message)
    {
        $code = '';
        $msg = '';
        $file = '';
        $line = '';
        @list($code, $msg, $file, $line) = explode("\t", $message);
        $code = str_replace('Code:', '', $code);
        $msg = str_replace('Msg:', '', $msg);
        $file = str_replace('File:', '', $file);
        $line = str_replace('Line:', '', $line);


        if ($this->format == 'html') {
            echo '<div style="background: #ffffe4;padding:5px 10px;border: 1px solid #d8d8d8;">
            <h2 style="padding:5px;margin:0px;font-size:18px;border-bottom: 1px solid #fff;margin-bottom:5px;">Vitex 错误提示</h2>
            <span style="display: inline-block;width:80px;font-size:13px;text-align: right;padding-right:10px;">错误代码:</span>' . $code . '<br />
            <span style="display: inline-block;width:80px;font-size:13px;text-align: right;padding-right:10px;">错误信息:</span>' . $msg . '<br />
            <span style="display: inline-block;width:80px;font-size:13px;text-align: right;padding-right:10px;">文件:</span>' . $file . '<br />
            <span style="display: inline-block;width:80px;font-size:13px;text-align: right;padding-right:10px;">错误行:</span>' . $line . '</div>';
        } else {
            echo sprintf("Vitex 错误提示:\n错误代码:%s\n错误信息:%s\n文件:%s\n错误行:%s\n", $code, $msg, $file, $line);
        }
        return $this;
    }
}