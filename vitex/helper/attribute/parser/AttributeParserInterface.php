<?php


namespace vitex\helper\attribute\parser;


interface AttributeParserInterface
{
    /**
     * 初步解析用户的所有注解
     * @param \ReflectionAttribute $attribute 属性
     * @param null $instance 属性的实例
     * @param \ReflectionProperty|\ReflectionClass|\ReflectionMethod|\ReflectionParameter|null $reflectInstance  反射的实例
     * @return mixed
     */
    public function parse(\ReflectionAttribute $attribute,$instance = null,\ReflectionProperty|\ReflectionClass|\ReflectionMethod | \ReflectionParameter $reflectInstance = null);

    /**
     * 对解析成功的注解进行处理，此处为处理程序
     * @param array $attributes
     * @return mixed
     */
    public function doFinal(array $attributes);
}