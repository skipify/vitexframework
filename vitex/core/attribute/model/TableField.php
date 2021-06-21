<?php


namespace vitex\core\attribute\model;

use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\EmptyParser;
use vitex\helper\attribute\parser\model\TableFieldParser;

/**
 * 存在于数据表中的字段
 * 一般用于实体类，可以排除字段
 * @package vitex\core\attribute\model
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class TableField implements AttributeInterface
{
    public function __construct(
        private string $alias = ''
    )
    {
    }

    public function getParse(): AttributeParserInterface
    {
        return new TableFieldParser();
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }


}