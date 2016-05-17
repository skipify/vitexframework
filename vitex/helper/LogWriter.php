<?php
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

namespace vitex\helper;

class LogWriter
{
    public $file;
    public function __construct($file = '')
    {
        $this->file = $file;
    }

    /**
     * @param  $level
     * @param  $message
     * @return $this
     */
    public function write($level, $message)
    {
        if (!$this->file) {
            list($code, $msg, $file, $line) = explode("\t", $message);
            $code                           = str_replace('Code:', '', $code);
            $msg                            = str_replace('Msg:', '', $msg);
            $file                           = str_replace('File:', '', $file);
            $line                           = str_replace('Line:', '', $line);

            echo '<div style="background: #ffffe4;padding:5px 10px;border: 1px solid #d8d8d8;">
                <h2 style="padding:5px;margin:0px;font-size:18px;border-bottom: 1px solid #fff;margin-bottom:5px;">Vitex 错误提示</h2>
                <span style="display: inline-block;width:80px;font-size:13px;text-align: right;padding-right:10px;">错误代码:</span>' . $code . '<br />
                <span style="display: inline-block;width:80px;font-size:13px;text-align: right;padding-right:10px;">错误信息:</span>' . $msg . '<br />
                <span style="display: inline-block;width:80px;font-size:13px;text-align: right;padding-right:10px;">文件:</span>' . $file . '<br />
                <span style="display: inline-block;width:80px;font-size:13px;text-align: right;padding-right:10px;">错误行:</span>' . $line . '</div>';
            return $this;
        }
        $str    = date('Y-m-d H:i:s') . ' - ' . $level . ' - ' . $message . PHP_EOL;
        $handle = fopen($this->file, 'a+');
        fwrite($handle, $str);
        fclose($handle);
        return $this;
    }
}
