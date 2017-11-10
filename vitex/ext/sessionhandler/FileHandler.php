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

namespace vitex\ext\sessionhandler;


use vitex\core\Exception;
use vitex\Vitex;

class FileHandler extends SessionHandler implements \SessionHandlerInterface
{
    /**
     * session 存储的路径
     * @var
     */
    private $path;
    /**
     * session的默认有效期
     * @var
     */
    private $lifetime;

    public function __construct()
    {
        parent::__construct();
        $path = $this->vitex->getConfig('session.file.path');
        $lifetime = $this->vitex->getConfig('session.lifetime');

        if(!$path){
            throw new Exception(Exception::CODE_PARAM_VALUE_ERROR.' 没有设置session存储目录',Exception::CODE_PARAM_ERROR_MSG);
        }
        $this->path = $path;
        $this->lifetime = $lifetime;
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {

        unlink($this->path.'/'.$session_id);
        return true;
    }

    public function gc($maxlifetime)
    {
        $expireTime = time() - $maxlifetime;
        foreach (glob($this->path.'/*') as $file) {
            if(is_file($file) && filemtime($file) < $expireTime){
                unlink($file);
            }
        }
    }

    public function open($save_path, $name)
    {
        return true;
    }

    public function read($session_id)
    {
        if (file_exists($path = $this->path.'/'.$session_id)) {
            $expireTime = time() - ($this->lifetime * 60);
            if (filemtime($path) >= $expireTime) {
                return $this->getContents($path);
            }
        }
        return '';
    }

    public function write($session_id, $session_data)
    {
        file_put_contents($this->path.'/'.$session_id, $session_data, LOCK_EX);
        return true;
    }

    /**
     * 加锁获取内容
     * @param $path
     * @return bool|string
     */
    private function getContents($path)
    {
        $contents = '';
        $handle = fopen($path, 'rb');
        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);
                    $size = filesize($path);
                    $contents = fread($handle, $size ?: 1);
                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }
        return $contents;
    }

}