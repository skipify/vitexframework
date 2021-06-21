<?php


namespace vitex\helper\attribute\parser;

use vitex\helper\attribute\exception\NotFoundClassException;
use vitex\helper\attribute\parser\validate\ValidateChain;
use vitex\Vitex;

/**
 * 注解操作的一些简单方法
 * @package vitex\helper\attribute\parser
 */
class AttributeTool
{

    /**
     * 判断某个类是否存在一个注解
     * @param string|\ReflectionClass $class
     * @param string $attribute
     * @return bool
     */
    public static function hasAttribute(string|\ReflectionClass $class, string $attribute): bool
    {
        if (is_string($class)) {
            if (!class_exists($class)) {
                throw new NotFoundClassException();
            }
            $class = new \ReflectionClass($class);
        }
        return $class->getAttributes($attribute) ? true : false;
    }

    /**
     * 获取需要验证格式的属性注解字段
     * @return array
     */
    public static function getValidateAttribute()
    {
        $vitex = Vitex::getInstance();
        /**
         * 把  [注解][类+属性] => 解释器 格式的数组改为
         * [类+属性][注解] => 解释器格式
         */
        $attributes = [];
        $allParsers = ValidateChain::$allParsers;
        foreach ($vitex->attributes as $attribute => $val) {
            if (!in_array($attribute, $allParsers)) {
                continue;
            }
            /**
             * $val 为一个类的注解结构 具体结构查看 ParseAttribute.php中的注释
             * $className 为 类的名字
             * $attribute 为注解的名字
             *
             */
            foreach ($val as $className => $v) {
                /**
                 * $v 是下面的结构
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
                 */
                foreach ($v['property'] as $property=>$parser){
                    $attributes[$className][$property][$attribute] = $parser;
                }
            }
        }
        return $attributes;
    }

    /**
     * 根据类型获得默认值
     * @param string $type
     * @return array|bool|float|int|string|null
     */
    public static function defVal(string $type)
    {
        switch ($type) {
            case 'string':
                return '';
            case 'int':
                return 0;
            case 'float':
                return 0.0;
            case 'array':
                return [];
            case 'bool':
                return true;
            default:
                return null;
        }
    }

}