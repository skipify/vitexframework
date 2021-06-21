<?php declare(strict_types=1);
/**
 * Vitex 一个基于php8.0开发的 快速开发restful API的微型框架
 * @version  2.0.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex\helper;

use vitex\core\event\EventEmitterInterface;
use vitex\helper\traits\SetTrait;


/**
 * This Is a Data box for run
 * for save some data in memory
 * you can use Object/array like opterator
 */
class Set implements \ArrayAccess, \Iterator, \Countable, EventEmitterInterface
{
    use SetTrait;

    /**
     * 初始化数组
     * @param array $data 数组
     */
    public function __construct(array $data = [])
    {
        $this->_data = $data;
    }

    /**
     * 导入数据
     * @param  array $data 导入数组数据
     * @return object $this
     */
    public function import($data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * 删除信息 会触发remove事件，参数为删除的值，不存在的值则为null
     * @param $key
     * @return string $key
     */
    public function remove($key)
    {
        $val = $this->_data[$key] ?? null;
        unset($this->_data[$key]);
        $this->emit('remove', [$val]);
        $this->emit('unset', [$val]);
    }

    /**
     * 清空信息
     * 会触发 clear 事件
     */
    public function clear()
    {
        $this->_data = array();
        $this->emit('clear');
    }

    /**
     * 获取所有的信息
     * @deprecated 不建议使用
     * @return array 数据集合的数据
     */
    public function all()
    {
        return $this->_data;
    }

    /**
     * 转为数组内容
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }


    public function __tostring(){

    }
}
