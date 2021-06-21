<?php


namespace vitex\helper\attribute;


use Doctrine\Common\Cache\ApcuCache;
use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\ParseDataInterface;
use vitex\helper\attribute\parser\validate\Validate;
use vitex\helper\attribute\parser\validate\ValidateChain;

/**
 * 解析所有的注解信息的类
 * 会返回所有的 class  method property 的注解
 * @package vitex\helper\attribute
 */
class ParseAttribute extends ReflectBase
{
    /**
     * 所有注解结果
     * 存储的数据格式为一个数组
     * [
     *      [Attribute::class][classname:methodname] => data  方法注解
     *      [Attribute::class][classname] => data  类注解
     *      [Attribute::class[classname:p:propertyname] => data 属性注解
     *      [Attribute:class][classname:methodname][parametername] => data  参数注解
     * ]
     *
     *
     *
    "attributename": {
        "classname1": {
            "class": "class",
            "method": {
                "method1": "method1",
                "method2": "method2"
            },
            "property": {
                "property1": "property1",
                "property2": "property2"
            },
            "parameter": {
                "methodname1": {
                    "parametername1": "parametername1",
                    "parametername2": "parametername2"
                },
                "methodname2": {
                    "parametername1": "parametername1",
                    "parametername2": "parametername2"
                }
            }
            }
        }
    }
     * @var array
     */
    private array $result = [];

    /**
     * 解析所有的注解  返回解析的结果
     * @throws \ReflectionException
     * @throws exception\NotCommonCLassException
     */
    public function parse()
    {
        $attributes = $this->getAttribute();
        foreach ($attributes as $attribute) {
            $this->parseClass($attribute['class']);
            $this->parseMethod($attribute['method']);
            $this->parseProperty($attribute['property']);
            $this->parseParameter($attribute['parameter']);
        }
        foreach ($this->result as $attributeName => $val) {
            foreach ($val as $className => $_attribute){
                /**
                 * @var $class ParseDataInterface
                 */
                $class = $_attribute['class'];
                $methods = $_attribute['method'] ?? [];
                $propertys = $_attribute['property'] ?? [];
                $parameters = $_attribute['parameter'] ?? [];

                if($class && !$class->isSlot()){
                    $class->getParse()->doFinal($val);
                }
                /**
                 * @var $method ParseDataInterface
                 */
                foreach ($methods as $method){
                    $method->getParse()->doFinal($val);
                }
                /**
                 * @var $property ParseDataInterface
                 */
                foreach ($propertys as $property){
                    $property->getParse()->doFinal($val);
                }

                foreach ($parameters as $method => $_parameters){
                    /**
                     * @var $parameter ParseDataInterface
                     */
                    foreach ($_parameters as $parameter){
                        $parameter->getParse()->doFinal($val);
                    }
                }
            }
        }
        /**
        {
            "attributename": {
                "classname1": {
                "class": "class",
                "method": {
                    "method1": "method1",
                    "method2": "method2"
                },
                "property": {
                    "property1": "property1",
                    "property2": "property2"
                },
                "parameter": {
                    "methodname1": {
                        "parametername1": "parametername1",
                        "parametername2": "parametername2"
                    },
                    "methodname2": {
                        "parametername1": "parametername1",
                        "parametername2": "parametername2"
                    }
                }
                }
            }
        }
         */
        return $this->result;
    }

    /**
     * 解析注解结束之后的操作
     * @param callable $callable
     */
    public function afterParse(callable $callable)
    {
        call_user_func_array($callable, $this->result);
    }


    /**
     * 根据注解名字获取所有注解
     * @param string $attributeName
     * @return array|mixed
     */
    public function getResultByAttribute(string $attributeName)
    {
        return $this->result[$attributeName] ?? [];
    }

    /**
     * 扫描指定的目录 解析出所有的注解
     * @return array
     * @throws \ReflectionException
     * @throws exception\NotCommonCLassException
     */
    public function getAttribute()
    {
        $files = Scaner::files();
        $parseResult = [];

        /**
         * @var $file FileInfo
         */
        foreach ($files as $file) {
            if (class_exists($file->getClass())) {
                $parseResult[$file->getClass()] = $this->parseAttribute($file->getClass());
            }
        }
        return $parseResult;
    }

    /**
     * 解析类注解
     * @param $class
     */
    private function parseClass($class)
    {
        $attributes = $class['attributes'];
        if (!$attributes) {
            return;
        }
        /**
         * @var $attribute \ReflectionAttribute
         */
        foreach ($attributes as $attribute) {
            if ($attribute === 'Attribute') {
                continue;
            }
            /**
             * @var $attributeInstance AttributeInterface
             */
            $attributeInstance = $attribute->newInstance();
            if (!($attributeInstance instanceof AttributeInterface)) {
                continue;
            }
            $parse = $attributeInstance->getParse();

            /**
             * @var $parseData ParseDataInterface
             */
            $parseData = $parse->parse($attribute, $attributeInstance, $class['class']);
            /**
             * 空的时候为不支持的注解
             */
            if ($parseData == null) {
                continue;
            }

            $parseData->setAttributedClassName($class['className']);
            $parseData->setTarget($class['class']);
            $this->result[$attribute->getName()][$class['className']]['class'] = $parseData;
        }
    }

    /**
     * 解析方法注解
     * @param array $methods
     */
    private function parseMethod(array $methods)
    {
        foreach ($methods as $method) {
            $attributes = $method['attributes'];
            if (!$attributes) {
                continue;
            }
            /**
             * @var $attribute \ReflectionAttribute
             */
            foreach ($attributes as $attribute) {
                if ($attribute === 'Attribute') {
                    continue;
                }
                /**
                 * @var $attributeInstance AttributeInterface
                 */
                $attributeInstance = $attribute->newInstance();
                if (!($attributeInstance instanceof AttributeInterface)) {
                    continue;
                }
                $parse = $attributeInstance->getParse();
                /**
                 * @var $methodInstance \ReflectionMethod
                 */
                $methodInstance = $method['method'];
                /**
                 * @var $parseData ParseDataInterface
                 */
                $parseData = $parse->parse($attribute, $attributeInstance, $methodInstance);

                /**
                 * 注入一些方法
                 */
                $parseData->setAttributedClassName($method['className']);
                $parseData->setAttributedMethodName($methodInstance->getName());
                $parseData->setTarget($method['method']);
                $this->result[$attribute->getName()][$method['className']]['method'][$methodInstance->getName()] = $parseData;
            }
        }
    }

    /**
     * 解析属性注解
     * @param $propertys
     */
    private function parseProperty($propertys)
    {
        foreach ($propertys as $property) {
            $attributes = $property['attributes'];
            if (!$attributes) {
                continue;
            }
            /**
             * @var $attribute \ReflectionAttribute
             */
            foreach ($attributes as $attribute) {
                if ($attribute === 'Attribute') {
                    continue;
                }
                /**
                 * @var $attributeInstance AttributeInterface
                 */
                $attributeInstance = $attribute->newInstance();
                if (!($attributeInstance instanceof AttributeInterface)) {
                    continue;
                }
                $parse = $attributeInstance->getParse();
                /**
                 * @var $propertyInstance \ReflectionProperty
                 */
                $propertyInstance = $property['property'];
                /**
                 * @var $parseData ParseDataInterface
                 */
                $parseData = $parse->parse($attribute, $attributeInstance, $propertyInstance);
                $parseData->setAttributedClassName($property['className']);
                $parseData->setAttributedPropertyName($propertyInstance->getName());
                $parseData->setTarget($property['property']);
                $this->result[$attribute->getName()][$property['className']]['property'][$propertyInstance->getName()] = $parseData;
            }
        }
    }


    /**
     * 解析属性注解
     * @param $parameters
     */
    private function parseParameter($parameters)
    {
        foreach ($parameters as $parameter) {
            $attributes = $parameter['attributes'];
            if (!$attributes) {
                continue;
            }
            /**
             * @var $attribute \ReflectionAttribute
             */
            foreach ($attributes as $attribute) {
                if ($attribute === 'Attribute') {
                    continue;
                }
                /**
                 * @var $attributeInstance AttributeInterface
                 */
                $attributeInstance = $attribute->newInstance();
                if (!($attributeInstance instanceof AttributeInterface)) {
                    continue;
                }
                $parse = $attributeInstance->getParse();
                /**
                 * @var $parameterInstance \ReflectionParameter
                 */
                $parameterInstance = $parameter['parameter'];
                /**
                 * @var $parseData ParseDataInterface
                 */
                $parseData = $parse->parse($attribute, $attributeInstance, $parameterInstance);
                $parseData->setAttributedClassName($parameter['className']);
                $parseData->setAttributedParameterName($parameterInstance->getName());
                $parseData->setTarget($parameter['parameter']);


                $this->result[$attribute->getName()][$parameter['className']]['parameter'][$parameter['method']][$parameterInstance->getName()] = $parseData;
            }
        }
    }

}