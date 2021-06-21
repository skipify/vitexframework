<?php

namespace vitex\helper\attribute\parser\sys;

use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\ParseDataBase;
use vitex\Vitex;

/**
 * 自动加载
 * @package vitex\helper\attribute\parser\sys
 */
class AutowireParser extends AttributeParserBase implements AttributeParserInterface
{
    private ParseDataBase $data;

    /**
     * 解析出所有的属性
     * @param \ReflectionAttribute $attribute
     * @param null $instance
     * @param null $reflectInstance
     * @return ParseDataBase
     */
    public function parse(\ReflectionAttribute $attribute, $instance = null, $reflectInstance = null)
    {
        $parseData = new ParseDataBase();
        $parseData->setParse($this);
        $parseData->setAttribute($attribute);
        $this->data = $parseData;
        return $parseData;
    }

    public function doFinal(array $attributes)
    {
        /**
         * @var $target \ReflectionProperty
         */
        $target = $this->data->getTarget();
        if (!($target instanceof \ReflectionProperty)) {
            return;
        }
        /**
         * @var $type \ReflectionNamedType
         */
        $type = $target->getType();
        if ($type->isBuiltin()) {
            return;
        }
        if (class_exists($type->getName())) {
            $vitex = Vitex::getInstance();
            /**
             * 属性类的实例
             */
            $instance = $vitex->container->get($type->getName());
            $classInstance = $target->getDeclaringClass()->newInstance();
            if($target->getModifiers() != \ReflectionProperty::IS_PUBLIC){
                $target->setAccessible(true);
            }
            $target->setValue($classInstance,$instance);
            $className = $target->getDeclaringClass()->getName();
            $vitex->container->set($className,$classInstance);
        }
    }

}