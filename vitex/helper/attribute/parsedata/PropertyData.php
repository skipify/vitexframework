<?php


namespace vitex\helper\attribute\parsedata;


use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\ParseDataBase;

/**
 * 过滤属性存储的信息
 * @package vitex\helper\attribute\parsedata
 */
class PropertyData extends ParseDataBase
{

    /**
     * 属性名字
     * @var string
     */
    private string $propertyName;

    /**
     * 属性类型
     * @var string
     */
    private string $propertyType;

    /**
     * @var AttributeInterface
     */
    private AttributeInterface $attributeInstance;
    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * @param string $propertyName
     */
    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    /**
     * @return string
     */
    public function getPropertyType(): string
    {
        return $this->propertyType;
    }

    /**
     * @param string $propertyType
     */
    public function setPropertyType(string $propertyType): void
    {
        $this->propertyType = $propertyType;
    }

    /**
     * @return AttributeInterface
     */
    public function getAttributeInstance(): AttributeInterface
    {
        return $this->attributeInstance;
    }

    /**
     * 设置注解
     * @param AttributeInterface $attributeInstance
     */
    public function setAttributeInstance(AttributeInterface $attributeInstance): void
    {
        $this->attributeInstance = $attributeInstance;
    }


}