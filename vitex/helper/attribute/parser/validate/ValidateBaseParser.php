<?php


namespace vitex\helper\attribute\parser\validate;


use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parsedata\PropertyData;
use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;

class ValidateBaseParser extends AttributeParserBase implements AttributeParserInterface
{
    /**
     * 可以注解注入的对象
     * @var array|string[]
     */
    private array $buildInType = [
        'int','float','string','bool','array'
    ];

    protected PropertyData $data;


    public function parse(\ReflectionAttribute $attribute, $instance = null, $reflectInstance = null)
    {

        $propertyData = new PropertyData();
        $propertyData->setParse($this);

        /**
         * @var $instance AttributeInterface
         */
        $instance = $instance ? $instance : $attribute->newInstance();
        $propertyData->setPropertyName($reflectInstance?->getName());
        $propertyData->setIsSlot(true);
        /**
         * @var $type \ReflectionNamedType
         */
        $type = $reflectInstance?->getType();
        if(!in_array($type,$this->buildInType)){
            return null;
        }
        $propertyData->setPropertyType($type->getName());
        $propertyData->setAttributeInstance($instance);
        $this->data = $propertyData;
        return $this->data;
    }

    public function doFinal(array $attributes)
    {

    }

    /**
     * 获取错误信息提示
     * @param $val
     * @param $instance
     * @return string|string[]
     */
    protected function getErrMsg($val, $instance)
    {
        return str_replace(['{attr}', '{val}'],
            [$instance->getFieldName() ? $instance->getFieldName() : $this->data->getTarget()->getName(),
                $val
            ],
            $instance->getErrMsg()
        );
    }
}