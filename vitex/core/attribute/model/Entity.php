<?php


namespace vitex\core\attribute\model;

use vitex\core\attribute\sys\Slot;

/**
 * 实体注解 标记为实体
 * @package vitex\core\attribute\model
 */
#[Slot]
#[\Attribute(\Attribute::TARGET_CLASS)]
class Entity
{

}