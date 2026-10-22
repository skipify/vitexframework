<?php declare(strict_types=1);

namespace vitex\service\session\drivers;

use vitex\service\session\SessionDriverInterface;
use vitex\service\session\SessionHandler;
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

        if (!$this->storePath) {
            $this->storePath = sys_get_temp_dir();
        }
    }

    /**
     * 防御纵深:校验key(session id)合法性
     * key会直接拼接文件路径，包含 ../ 等字符会导致任意文件读写删除
     * @param string $key
     * @return bool
     */
    private function isSafeKey(string $key): bool
    {
        return SessionHandler::isValidSessionId($key);
    }

    public function get($key)
    {
        if (!$this->isSafeKey((string)$key)) {
            return null;
        }
        if (file_exists($path = $this->storePath . '/' . $key)) {
            $expireTime = time() - ($this->lifetime * 60);
            if (filemtime($path) >= $expireTime) {
                return $this->getContents($path);
            }
        }
    }

    public function set($key, $val, $expire = 60)
    {
        if (!$this->isSafeKey((string)$key)) {
            return false;
        }
        $bytes = file_put_contents($this->storePath . '/' . $key, $val, LOCK_EX);
        if ($bytes === false) {
            return false;
        }
        @chmod($this->storePath . '/' . $key, 0600);
        return true;
    }

    public function delete($key)
    {
        if (!$this->isSafeKey((string)$key)) {
            return false;
        }
        @unlink($this->storePath . '/' . $key);
        return true;
    }

    public function gc($maxlifetime)
    {
        $expireTime = time() - ($maxlifetime * 60);
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