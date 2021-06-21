<?php


namespace vitex\helper\attribute\parser\sys;


use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\exception\NotSupportAttributeException;
use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\ParseDataBase;
use vitex\Vitex;

/**
 * 解析方法缓存的注解
 * @package vitex\helper\attribute\parser\sys
 */
class CacheParser extends AttributeParserBase implements AttributeParserInterface
{
    private ParseDataBase $data;

    public function parse(\ReflectionAttribute $attribute, $instance = null, $reflectInstance = null)
    {
        if(!function_exists('runkit7_method_rename')){
            throw  new NotSupportAttributeException("Not Found Exception runkit7. If you Want use `Caching` Annotation , Please install runkit7 by `pecl install runkit7`");
        }
        $data = new ParseDataBase();

        $data->setParse($this);
        $data->setAttribute($attribute);
        $this->data = $data;
        return $data;
    }

    public function doFinal(array $attributes)
    {
        $vitex = Vitex::getInstance();
        /**
         * @var $target \ReflectionMethod
         */
        $target = $this->data->getTarget();
        $methodName = $target->getName();
        $className = $target->getDeclaringClass()->getName();
        $targetInstance = $target->getDeclaringClass()->newInstance();

        /**
         * 原来的方法改名
         */
        $newMethodName = "VitexCacheOverride_".$methodName;
        \runkit7_method_rename($className,$methodName,$newMethodName);
        /**
         * 新的函数体
         * 需要根据是否是swoole模式判断是否是跨请求的处理
         */
        //todo  此处swoole的扩展可能不是很安全
        $str = '
            $cache = vitex\helper\attribute\parsedata\CacheData::instance();
            $args = func_get_args();
            $key = "'.$className.'-'.$methodName.'".(count($args) == 0 ? "-":json_encode($args));
            if(defined("IS_SWOOLE")){
                $vitex = \vitex\Vitex::getInstance();
                $key = $vitex->requestId . $key;
            }
            if ($cache->fetch($key) !== false) {
               return $cache->fetch($key);
            }
            $ret = $this->'.$newMethodName.'(...$args);
            $cache->save($key, $ret);
            return $ret;
        ';
        $paramters = $target->getParameters();
        $paramterStrArr = [];
        foreach ($paramters as $parameter){
            $paramterStrArr[] = $parameter->getName();
        }

        /**
         * @var $returnType \ReflectionNamedType
         */
        $returnType = $target->getReturnType();

        \runkit7_method_add($className,
            $methodName,
            implode(',',$paramterStrArr),
            $str,
            RUNKIT7_ACC_PUBLIC,
            null,
            $returnType?$returnType->getName():''
        );

        $vitex->container->set($target->getDeclaringClass()->getName(),$targetInstance);
    }

}