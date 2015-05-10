<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package Vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace Vitex\Helper;

/**
 * This Is a Data box for run
 * for save some data in memory
 * you can use Object/array like opterator
 */

class Set implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * 初始化数组
     * @param  array    $data 数组
     * @return object
     */
    public function __construct(array $data = [])
    {
        $this->_data = $data;
    }

    /**
     * 导入数据
     * @param  arrat  $data   导入数组数据
     * @return object $this
     */
    public function import($data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * 删除信息
     * @return string $key
     */
    public function remove($key)
    {
        unset($this->_data[$key]);
    }

    /**
     * 清空信息
     */
    public function clear()
    {
        $this->_data = array();
    }

    /**
     * 获取所有的信息
     * @return array 数据集合的数据
     */
    public function all()
    {
        return $this->_data;
    }

    use SetMethod;
}
