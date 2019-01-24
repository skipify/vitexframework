<?php declare(strict_types=1);

namespace vitex\service\session\drivers;

use vitex\service\session\SessionDriverInterface;
use vitex\Vitex;

/**
 * 文件存储session
 */
class FileDriver implements SessionDriverInterface
{
    /**
     * 文件存储路径
     * @var
     */

    private $storePath;

    /**
     * session 有效期
     * @var
     */

    private $lifetime;

    public function __construct()
    {
        $vitex = Vitex::getInstance();
        $this->storePath = $vitex->getConfig('session.file.path');
        $this->lifetime = $vitex->getConfig('session.lifetime');

        if(!$this->storePath){
            $this->storePath = sys_get_temp_dir();
        }
    }

    public function get($key)
    {
        if (file_exists($path = $this->storePath . '/' . $key)) {
            $expireTime = time() - ($this->lifetime * 60);
            if (filemtime($path) >= $expireTime) {
                return $this->getContents($path);
            }
        }
    }

    public function set($key, $val, $expire = 60)
    {
        file_put_contents($this->storePath . '/' . $key, $val, LOCK_EX);
        return true;
    }

    public function delete($key)
    {
        unlink($this->storePath . '/' . $key);
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