<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
namespace vitex\helper;

/**
 * 用于实现ArrayAccess,Iterator,Countable的接口方法的Trait
 */
trait SetMethod
{
    protected $_data = [];
    protected $_pos  = 0;

    public function offsetExists($val)
    {
        return isset($this->_data[$val]);
    }

    public function offsetSet($key, $val)
    {
        if (is_null($key)) {
            $this->_data[] = $val;
        } else {
            $this->_data[$key] = $val;
        }
    }

    public function offsetGet($key)
    {
        return $this->_data[$key] ?? null;
    }

    public function offsetUnset($key)
    {
        unset($this->_data[$key]);
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
