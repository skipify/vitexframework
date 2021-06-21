<?php

namespace vitex\service\config;

/**
 * 配置项的配置
 * 为了方便用户
 * @package vitex\service\config
 */
interface ConfigInterface
{
    /**
     * 把配置转为数组
     * @return array
     */
    public function toArray(): array;

    /**
     * 把数组转为一个配置类
     * @param array $config
     * @return ConfigInterface
     */
    public static function fromArray(array $config): ConfigInterface;
}