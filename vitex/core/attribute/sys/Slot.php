<?php


namespace vitex\core\attribute\sys;

/**
 * 插槽注解
 * 正常的注解是在路由之前解析使用
 * 添加了此注解后就不在自动解析，需要在使用的时候再自助解析
 * @package vitex\core\attribute\sys
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Slot
{

}