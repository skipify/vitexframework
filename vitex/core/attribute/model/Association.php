<?php


namespace vitex\core\attribute\model;


use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\model\AssociationParser;

/**
 * 在一个实体中关联另一个实体的时候的关联注解
 * 此注解适用于  多对一的查询，因此 注解的数据为单条
 * 使用此注解要特别的注意  次注解适用于多表查询或者连接查询，因此 需要关联的实体的字段和主实体的字段名字不应该重复
 * 确定需要的同名字段应该是单独使用 as标记
 * @package vitex\core\attribute\model
 */
#[Slot]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Association implements AttributeInterface
{
    /**
     * 属性名=> field 字段名
     * 此属性为 实体类属性字段与数据库字段的一个映射关系
     * @param array $fieldMap 字段映射表
     */
    public function __construct(private array $fieldMap)
    {

    }

    /**
     * @return array
     */
    public function getFieldMap(): array
    {
        return $this->fieldMap;
    }

    /**
     * @param array $fieldMap
     */
    public function setFieldMap(array $fieldMap): void
    {
        $this->fieldMap = $fieldMap;
    }

    public function getParse(): AttributeParserInterface
    {
        return new AssociationParser();
    }
}