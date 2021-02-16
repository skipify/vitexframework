<?php declare(strict_types=1);
/**
 * Vitex 一个基于php7.0开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
/**
 * 分组路由内容容器
 */

namespace vitex\core;


class RouteBundle
{
    /**
     * 存储的路由分组匹配前缀
     * @var string
     */
    public $pattern;
    /**
     * 路由调用的应用名称
     * @var string
     */
    public $appName;
    /**
     * 分组的内容
     * 引入的分组文件 或者是一个callable的回调
     * @var mixed
     */
    public $group;

    public function __construct($pattern, $groupFile, $appName)
    {
        $this->pattern = trim($pattern, '/');
        $this->group = $groupFile;
        $this->appName = $appName;
    }

    /**
     * 获取分组前缀
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * 获取分组内容
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * 获取调用的应用名称
     * @return string
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * 获取所有的匹配路由配置
     * @param array $bundles
     * @param $pattern
     * @return array|RouteBundle
     */
    public static function getByPattern(array $bundles, $pattern)
    {
        $pattern = trim($pattern, '/');
        $items = [];
        foreach ($bundles as $bundle) {
            /**
             * @var $bundle RouteBundle
             */
            if ($bundle->getPattern() == $pattern) {
                $items = $bundle;
            }
        }
        return $items;
    }
}