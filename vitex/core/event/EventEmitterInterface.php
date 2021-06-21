<?php declare(strict_types=1);

/*
 * 事件接口
 */

namespace vitex\core\event;

interface EventEmitterInterface
{
    public function on($event, callable $listener);
    public function once($event, callable $listener);
    public function off($event, callable $listener);
    public function offAll($event = null);
    public function getListeners($event = null);
    public function emit($event, array $arguments = []);
}
