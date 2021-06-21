<?php


namespace vitex\core\attribute\parameter;

use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\parameter\RequestBodyParser;

/**
 * 控制器的方法注解 用于接收参数到一个实体类中
 * @package vitex\core\attribute\parameter
 */
#[Slot]
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class RequestBody implements AttributeInterface
{
    public function getParse(): AttributeParserInterface
    {
        return new RequestBodyParser();
    }

}