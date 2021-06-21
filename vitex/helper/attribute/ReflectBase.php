<?php

namespace vitex\helper\attribute;

use vitex\helper\attribute\exception\NotCommonCLassException;

class ReflectBase
{
    /**
     * 所有的参数  需要在解析方法的时候解析出对象
     * @var array
     */
    private array $parameters = [];

    /**
     * @param $class
     * @return array
     * @throws NotCommonCLassException
     * @throws \ReflectionException
     */
    protected function parseAttribute($class)
    {
        $reflection = new \ReflectionClass($class);
        if ($reflection->isInterface()) {
            throw  new NotCommonCLassException($class . " is a Interface");
        }

        $methods = $reflection->getMethods();
        $properties = $reflection->getProperties();
        $classAttributes = $reflection->getAttributes();

        return [
            'class' => [
                'type' => \Attribute::TARGET_CLASS,
                'className' => $class,
                'class' => $reflection,
                'attributes' => $classAttributes
            ],
            'method' => $this->parseMethodAttribute($class, $methods),
            'property' => $this->parsePropertyAttribute($class, $properties),
            'parameter' => $this->parseParameterAttribute($class, $this->parameters)
        ];
    }

    /**
     * @param $methods
     * @return array
     */
    protected function parseMethodAttribute($className, array $methods)
    {
        $this->parameters = [];
        $methodAttribute = [];
        /**
         * @var $method \ReflectionMethod
         */
        foreach ($methods as $method) {
            foreach ($method->getParameters() as $parameter) {
                if ($parameter->getAttributes()) {
                    $this->parameters[] = [
                         $parameter,
                         $method->getName()
                    ];
                }
            }
            $attributes = $method->getAttributes();
            if ($attributes) {
                $methodAttribute[] = [
                    'type' => \Attribute::TARGET_METHOD,
                    'className' => $className,
                    'method' => $method,
                    'attributes' => $attributes
                ];
            }
        }
        return $methodAttribute;
    }

    /**
     *
     * @param $properties
     * @return array
     */
    protected function parsePropertyAttribute($className, array $properties)
    {
        $propertyAttribute = [];
        /**
         * @var $property \ReflectionProperty
         */

        foreach ($properties as $property) {
            $attributes = $property->getAttributes();
            if ($attributes) {
                $propertyAttribute[] = [
                    'type' => \Attribute::TARGET_PROPERTY,
                    'className' => $className,
                    'property' => $property,
                    'attributes' => $attributes
                ];
            }
        }
        return $propertyAttribute;
    }

    /**
     *
     * @param $parameters
     * @return array
     */
    protected function parseParameterAttribute($className, array $parameters)
    {
        //print_r($parameters);
        $parametersAttribute = [];
        /**
         * @var $property \ReflectionParameter
         */

        foreach ($parameters as list($parameter,$method)) {
            $attributes = $parameter->getAttributes();
            if ($attributes) {
                $parametersAttribute[] = [
                    'type' => \Attribute::TARGET_PROPERTY,
                    'className' => $className,
                    'parameter' => $parameter,
                    'method' => $method,
                    'attributes' => $attributes
                ];
            }
        }
        return $parametersAttribute;
    }
}