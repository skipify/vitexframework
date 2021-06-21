<?php


namespace vitex\service\cache\config;


use vitex\service\config\ConfigInterface;

/**
 * PHP缓存配置实体
 * @package vitex\service\cache\config
 */
class PhpFileCacheConfig implements ConfigInterface
{
    private string $path;

    private string $extension = '.vitex.php';

    private int $umask = 0002;

    public function __construct()
    {
        $this->path = sys_get_temp_dir();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @return int
     */
    public function getUmask(): int
    {
        return $this->umask;
    }

    /**
     * @param int $umask
     */
    public function setUmask(int $umask): void
    {
        $this->umask = $umask;
    }


    public function toArray(): array
    {
        return [
            'path' => $this->getPath(),
            'umask' => $this->getUmask(),
            'extension' => $this->getExtension()
        ];
    }

    public static function fromArray(array $config): self
    {
        $instance = new self();
        foreach ($config as $key => $val) {
            $instance->{$key} = $val;
        }
        return $instance;
    }
}