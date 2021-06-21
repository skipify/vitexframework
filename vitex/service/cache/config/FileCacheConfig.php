<?php

namespace vitex\service\cache\config;


use vitex\service\config\ConfigInterface;

/**
 * 文件缓存配置
 * @package vitex\service\cache\config
 */
class FileCacheConfig implements ConfigInterface
{
    private string $path = '';

    private string $extension = '.vitex.data';

    private int $umask = 0002;

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