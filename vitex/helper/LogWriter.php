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
    public $handle;
    public function __construct($file = '')
    {
        if (is_resource($file)) {
            $this->handle = $file;
        } else if ($file) {
            $this->handle = fopen($file, 'a+');
        }
    }

    /**
     * @param  $level
     * @param  $message
     * @return $this
     */
    public function write($level, $message)
    {
        if (!$this->handle) {
            echo '<div><b>Error:</b>' . $message . '<br /></div>';
            return $this;
        }
        $str = date('Y-m-d H:i:s') . ' - ' . $level . ' - ' . $message . PHP_EOL;
        fwrite($this->handle, $str);
        fclose($this->handle);
        return $this;
    }
}
