<?php declare(strict_types=1);
/**
 * 日志信息存储到文件中
 */

namespace vitex\service\log\handlers;


use vitex\service\log\LogHandlerInterface;

class File implements LogHandlerInterface
{
    /**
     * 存储路径
     * @var
     */
    private $storePath;
    public function __construct($storePath = '')
    {
        $this->storePath = $storePath;
        if(!$this->storePath){
            $this->storePath = sys_get_temp_dir() .'/vitex-log.txt';
        }
    }

    public function write($level, $message)
    {
        $str    = date('Y-m-d H:i:s') . ' - ' . $level . ' - ' . $message . PHP_EOL;
        $handle = fopen($this->storePath, 'a+');
        fwrite($handle, $str);
        fclose($handle);
        return $this;
    }
}