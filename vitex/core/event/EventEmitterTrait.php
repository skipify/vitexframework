<?php declare(strict_types=1);

/*
 * 事件trait 方便实现接口
 */

namespace vitex\core\event;

use Invoker\Invoker;
use vitex\core\Exception;

trait EventEmitterTrait
{
    protected $listeners = [];
    protected $onceListeners = [];

    /**
     * 注册一个事件
     * @param $event
     * @param callable $listener
     * @return $this
     * @throws Exception
     */
    public function on($event, callable $listener)
    {
        if ($event === null) {
            throw new Exception(Exception::CODE_PARAM_ERROR_MSG .' 事件名称不得为null',Exception::CODE_PARAM_ERROR);
        }

        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $listener;

        return $this;
    }

    /**
     * 注册一个只执行一次的事件
     * @param $event
     * @param callable $listener
     * @return $this
     * @throws Exception
     */
    public function once($event, callable $listener)
    {
        if ($event === null) {
            throw new Exception(Exception::CODE_PARAM_ERROR_MSG .' 事件名称不得为null',Exception::CODE_PARAM_ERROR);
        }

        if (!isset($this->onceListeners[$event])) {
            $this->onceListeners[$event] = [];
        }

        $this->onceListeners[$event][] = $listener;

        return $this;
    }

    /**
     * 删除一个事件
     * @param $event
     * @param callable $listener
     * @throws Exception
     */
    public function off($event, callable $listener)
    {
        if ($event === null) {
            throw new Exception(Exception::CODE_PARAM_ERROR_MSG .' 事件名称不得为null',Exception::CODE_PARAM_ERROR);
        }

        if (isset($this->listeners[$event])) {
            $index = \array_search($listener, $this->listeners[$event], true);
            if (false !== $index) {
                unset($this->listeners[$event][$index]);
                if (\count($this->listeners[$event]) === 0) {
                    unset($this->listeners[$event]);
                }
            }
        }

        if (isset($this->onceListeners[$event])) {
            $index = \array_search($listener, $this->onceListeners[$event], true);
            if (false !== $index) {
                unset($this->onceListeners[$event][$index]);
                if (\count($this->onceListeners[$event]) === 0) {
                    unset($this->onceListeners[$event]);
                }
            }
        }
    }

    public function offAll($event = null)
    {
        if ($event !== null) {
            unset($this->listeners[$event]);
        } else {
            $this->listeners = [];
        }

        if ($event !== null) {
            unset($this->onceListeners[$event]);
        } else {
            $this->onceListeners = [];
        }
    }

    public function getListeners($event = null): array
    {
        if ($event === null) {
            $events = [];
            $eventNames = \array_unique(
                \array_merge(\array_keys($this->listeners), \array_keys($this->onceListeners))
            );
            foreach ($eventNames as $eventName) {
                $events[$eventName] = \array_merge(
                    $this->listeners[$eventName] ?? [],
                    $this->onceListeners[$eventName] ?? []
                );
            }
            return $events;
        }

        return \array_merge(
            $this->listeners[$event] ?? [],
            $this->onceListeners[$event] ?? []
        );
    }

    public function emit($event, array $arguments = [])
    {
        if ($event === null) {
            throw new Exception(Exception::CODE_PARAM_ERROR_MSG .' 事件名称不得为null',Exception::CODE_PARAM_ERROR);
        }
        /**
         * 调用
         */
        $invoker = new Invoker();
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $invoker->call($listener,$arguments);
            }
        }

        if (isset($this->onceListeners[$event])) {
            $listeners = $this->onceListeners[$event];
            unset($this->onceListeners[$event]);
            foreach ($listeners as $listener) {
                $invoker->call($listener,$arguments);
            }
        }
    }
}
