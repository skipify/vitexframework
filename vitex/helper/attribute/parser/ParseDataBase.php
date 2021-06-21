<?php


namespace vitex\helper\attribute\parser;

/**
 * 注解实体的基类
 * @package vitex\helper\attribute\parser
 */
class ParseDataBase
{
    /**
     * 当前注解
     * @var \ReflectionAttribute
     */
    private \ReflectionAttribute $attribute;

    /**
     * 当前注解的解释器
     * @var AttributeParserInterface
     */
    private AttributeParserInterface $parse;

    /**
     * 被注解的类名
     * @var string
     */
    private string $attributedClassName;
    /**
     * 被注解的方法名
     * @var string
     */
    private string $attributedMethodName;
    /**
     * 被注解的属性名字
     * @var string
     */
    private string $attributedPropertyName;

    /**
     * 被注解的参数
     * @var string
     */
    private string $attributeParameterName;

    private \ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter $target;

    /**
     * 是否是插槽类的注解
     */
    private bool $isSlot = false;

    /**
     * @return \ReflectionAttribute
     */
    public function getAttribute(): \ReflectionAttribute
    {
        return $this->attribute;
    }

    /**
     * @param \ReflectionAttribute $attribute
     */
    public function setAttribute(\ReflectionAttribute $attribute): void
    {
        $this->attribute = $attribute;
    }

    /**
     * @return string
     */
    public function getAttributedClassName(): string
    {
        return $this->attributedClassName;
    }

    /**
     * @param string $attributedClassName
     */
    public function setAttributedClassName(string $attributedClassName): void
    {
        $this->attributedClassName = $attributedClassName;
    }

    /**
     * @return string
     */
    public function getAttributedMethodName(): string
    {
        return $this->attributedMethodName;
    }

    /**
     * @param string $attributedMethodName
     */
    public function setAttributedMethodName(string $attributedMethodName): void
    {
        $this->attributedMethodName = $attributedMethodName;
    }

    /**
     * @return string
     */
    public function getAttributedPropertyName(): string
    {
        return $this->attributedPropertyName;
    }

    /**
     * @param string $attributedPropertyName
     */
    public function setAttributedPropertyName(string $attributedPropertyName): void
    {
        $this->attributedPropertyName = $attributedPropertyName;
    }

    /**
     * @return AttributeParserInterface
     */
    public function getParse(): AttributeParserInterface
    {
        return $this->parse;
    }

    /**
     * @param AttributeParserInterface $parse
     */
    public function setParse(AttributeParserInterface $parse): void
    {
        $this->parse = $parse;
    }

    public function setTarget(\ReflectionMethod|\ReflectionClass|\ReflectionProperty|\ReflectionParameter $target)
    {
        $this->target = $target;
    }

    /**
     * 反射示例，反射的方法 类、属性或者参数实例
     * @return \ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter
     */
    public function getTarget(): \ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter
    {
        return $this->target;
    }

    /**
     * @return bool
     */
    public function isSlot(): bool
    {
        return $this->isSlot;
    }

    /**
     * @param bool $isSlot
     */
    public function setIsSlot(bool $isSlot): void
    {
        $this->isSlot = $isSlot;
    }

    /**
     * 获得被注解属性名
     * @return string
     */
    public function getAttributedParameterName(): string
    {
        return $this->attributeParameterName;
    }

    /**
     * 设置背注解的属性名
     * @param string $attributedParameterName
     */
    public function setAttributedParameterName(string $attributedParameterName): void
    {
        $this->attributeParameterName = $attributedParameterName;
    }
}