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

namespace vitex\helper;

use vitex\core\event\EventEmitterTrait;


/**
 * 用于实现ArrayAccess,Iterator,Countable的接口方法的Trait
 */
trait SetMethod
{
    use EventEmitterTrait;

    protected $_data = [];
    protected $_pos = 0;

    public function offsetExists($val)
    {
        return isset($this->_data[$val]);
    }

    /**
     * 会触发添加事件
     * @param $key
     * @param $val
     */
    public function offsetSet($key, $val)
    {
        if (is_null($key)) {
            $this->_data[] = $val;
            $this->emit('set', $this->_data);
        } else {
            $this->_data[$key] = $val;
            $this->emit('set', $this->_data);
        }
    }

    public function offsetGet($key)
    {
        return $this->_data[$key] ?? null;
    }

    /**
     * 会触发 remove/unset事件 事件参数为
     * @param $key
     */
    public function offsetUnset($key)
    {
        $val = $this->_data[$key] ?? null;
        unset($this->_data[$key]);
        $this->emit('remove', [$val]);
        $this->emit('unset', [$val]);
    }

    public function __isset($val)
    {
        return $this->offsetExists($val);
    }

    public function __set($key, $val)
    {
        $this->offsetSet($key, $val);
    }

    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    //Iterator methods
    //
    public function rewind()
    {
        reset($this->_data);
    }

    public function key()
    {
        return key($this->_data);
    }

    public function next()
    {
        return next($this->_data);
    }

    public function current()
    {
        return current($this->_data);
    }

    public function valid()
    {
        return $this->offsetExists($this->key());
    }

    public function count()
    {
        return count($this->_data);
    }
}
