<?php


namespace vitex\helper\attribute\parser;

/**
 * 解析注解数据存储结果的实体接口
 * @package vitex\helper\attribute\parser
 */
interface ParseDataInterface
{

    /**
     * 设置注解
     * @return \ReflectionAttribute
     */
    public function getAttribute(): \ReflectionAttribute;

    /**
     * 获得注解
     * @param \ReflectionAttribute $attribute
     */
    public function setAttribute(\ReflectionAttribute $attribute): void;

    /**
     * 获得背注解的类名
     * @return string
     */
    public function getAttributedClassName(): string;

    /**
     * 设置被注解的类名
     * @param string $attributedClassName
     */
    public function setAttributedClassName(string $attributedClassName): void;

    /**
     * 获得被注解的方法名
     * @return string
     */
    public function getAttributedMethodName(): string;

    /**
     * 设置被注解的方法名
     * @param string $attributedMethodName
     */
    public function setAttributedMethodName(string $attributedMethodName): void;

    /**
     * 获得被注解属性名
     * @return string
     */
    public function getAttributedPropertyName(): string;

    /**
     * 设置背注解的属性名
     * @param string $attributedPropertyName
     */
    public function setAttributedPropertyName(string $attributedPropertyName): void;

    /**
     * 获得被注解属性名
     * @return string
     */
    public function getAttributedParameterName(): string;

    /**
     * 设置背注解的属性名
     * @param string $attributedParameterName
     */
    public function setAttributedParameterName(string $attributedParameterName): void;

    /**
     * 获得注解解析器
     * @return AttributeParserInterface
     */
    public function getParse(): AttributeParserInterface;

    /**
     * 设置注解解析器
     * @param AttributeParserInterface $parse
     */
    public function setParse(AttributeParserInterface $parse): void;

    /**
     * 设置注解的目标
     * @param \ReflectionClass|\ReflectionMethod|\ReflectionProperty $target
     * @return mixed
     */
    public function setTarget(\ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter $target);

    /**
     * 获取注解的目标
     * @return \ReflectionClass|\ReflectionMethod|\ReflectionProperty
     */
    public function getTarget(): \ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionParameter;

    /**
     * @return bool
     */
    public function isSlot(): bool;

    /**
     * @param bool $isSlot
     */
    public function setIsSlot(bool $isSlot): void;

}