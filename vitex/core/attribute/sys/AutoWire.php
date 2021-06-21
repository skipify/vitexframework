<?php


namespace vitex\core\attribute\sys;
use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\sys\AutowireParser;

/**
 * 自动加载
 * 加有此注解的属性会自动注入， 属性可以使用private修饰
 * @package vitex\core\attribute\sys
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AutoWire implements AttributeInterface
{
    public function __construct(){

    }

    public function getParse(): AttributeParserInterface
    {
        return new AutowireParser();
    }
}