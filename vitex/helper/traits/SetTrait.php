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

namespace vitex\helper\traits;

use vitex\core\event\EventEmitterTrait;


/**
 * 用于实现ArrayAccess,Iterator,Countable的接口方法的Trait
 */
trait SetTrait
{
    use EventEmitterTrait;

    protected $_data = [];
    protected $_pos = 0;

    public function offsetExists($val): bool
    {
        return isset($this->_data[$val]);
    }

    /**
     * 会触发添加事件
     * @param $key
     * @param $val
     */
    public function offsetSet($key, $val): void
    {
        if (is_null($key)) {
            $this->_data[] = $val;
            $this->emit('set', $this->_data);
        } else {
            $this->_data[$key] = $val;
            $this->emit('set', $this->_data);
        }
    }

    public function offsetGet($key): mixed
    {
        return $this->_data[$key] ?? null;
    }

    /**
     * 会触发 remove/unset事件 事件参数为
     * @param $key
     */
    public function offsetUnset($key): void
    {
        $val = $this->_data[$key] ?? null;
        unset($this->_data[$key]);
        $this->emit('remove', [$val]);
        $this->emit('unset', [$val]);
    }

    public function __isset($val): bool
    {
        return $this->offsetExists($val);
    }

    public function __set($key, $val): void
    {
        $this->offsetSet($key, $val);
    }

    public function __get($key): mixed
    {
        return $this->offsetGet($key);
    }

    public function __unset($key): void
    {
        $this->offsetUnset($key);
    }

    //Iterator methods
    //
    public function rewind(): void
    {
        reset($this->_data);
    }

    public function key(): mixed
    {
        return key($this->_data);
    }

    public function next(): void
    {
        next($this->_data);
    }

    public function current(): mixed
    {
        return current($this->_data);
    }

    public function valid(): bool
    {
        return $this->offsetExists($this->key());
    }

    public function count(): int
    {
        return count($this->_data);
    }
}
