<?php declare(strict_types=1);
/**
 * 事件基类
 */

namespace vitex\core\event;

class EventEmitter implements EventEmitterInterface
{
    use EventEmitterTrait;
}
