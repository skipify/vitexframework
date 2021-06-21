<?php


namespace vitex\core\attribute\model;

use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\EmptyParser;

/**
 * 不存在于数据表中的字段
 * 一般用于实体类，可以排除字段
 * @package vitex\core\attribute\model
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class NotTableField implements AttributeInterface
{
    public function getParse(): AttributeParserInterface
    {
        return new EmptyParser();
    }
}