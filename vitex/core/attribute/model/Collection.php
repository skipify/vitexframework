<?php


namespace vitex\core\attribute\model;

use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\model\CollectionParser;

/**
 * 集合字段对应
 * 此注解适用于  一对多的查询，因此 注解的数据为一个数组
 * 如果数据库连表查询是集合类型则会根据此注解的数据进行对应
 * 使用此注解要特别的注意  次注解适用于多表查询或者连接查询，因此 需要关联的实体的字段和主实体的字段名字不应该重复
 * 确定需要的同名字段应该是单独使用 as标记
 * @package vitex\core\attribute\model
 */
#[Slot]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Collection implements AttributeInterface
{
    /**
     * 属性名=> field 字段名
     * 此属性为 实体类属性字段与数据库字段的一个映射关系
     * 当前字段肯定为一个集合类字段 也就是数组
     * @param array $fieldMap 字段映射表
     * @param $class string 集合里面泛型的值
     */
    public function __construct(private array $fieldMap,private string $class)
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

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }



    public function getParse(): AttributeParserInterface
    {
        return new CollectionParser();
    }
}