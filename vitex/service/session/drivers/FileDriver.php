<?php declare(strict_types=1);

namespace vitex\service\session\drivers;

use Doctrine\Common\Cache\FilesystemCache;
use vitex\service\cache\CacheUtil;
use vitex\service\session\SessionConfig;
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
        $sessionConfig = SessionConfig::fromArray($vitex->getConfig('session'));
        $this->storePath = $sessionConfig->getPath();
        $this->lifetime = $sessionConfig->getLifetime() * 60;

        if (!$this->storePath) {
            $this->storePath = sys_get_temp_dir();
        }
    }

    public function get($key)
    {
        if (file_exists($path = $this->storePath . '/' . $key)) {
            $expireTime = VITEX_NOW - ($this->lifetime * 60);
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

    public function gc($maxlifetime)
    {
        $expireTime = VITEX_NOW - ($maxlifetime * 60);
        foreach (glob($this->storePath.'/*') as $file) {
            if (is_file($file) && filemtime($file) < $expireTime) {
                @unlink($file);
            }
        }
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