<?php

namespace vitex\helper\attribute\parser\parameter;


use vitex\constant\Mime;
use vitex\core\attribute\parameter\RequestBody;
use vitex\core\attribute\validate\Validate;
use vitex\core\Env;
use vitex\core\Exception;
use vitex\ext\Filter;
use vitex\helper\attribute\exception\NotFoundClassException;
use vitex\helper\attribute\parsedata\ParameterData;
use vitex\helper\attribute\parsedata\PropertyData;
use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\AttributeTool;
use vitex\helper\attribute\parser\validate\ValidateAttributeInterface;
use vitex\Vitex;

/**
 * 解析RequestBody注解
 * 只对 public 修饰的属性才可以用
 * @package vitex\helper\attribute\parser\parameter
 */
class RequestBodyParser extends AttributeParserBase implements AttributeParserInterface
{
    private ParameterData $data;

    public function parse(\ReflectionAttribute $attribute, $instance = null, $reflectInstance = null)
    {

        /**
         * @var $type \ReflectionNamedType
         */
        $type = $reflectInstance?->getType();


        $parameterData = new ParameterData();
        $parameterData->setParse($this);
        $parameterData->setParameterName($reflectInstance?->getName());
        $parameterData->setParameterType($type);
        $parameterData->setIsSlot(true);
        $this->data = $parameterData;
        return $this->data;
    }

    public function doFinal(array $attributes)
    {

    }

    /**
     * 把参数注解指定的类的数据加到参数里
     * @param $entity mixed 注解的类型
     * @param array $validates 校验器
     * 属性注解列表  $validates
    {
        "classname":{
            "property1":{
                "attributename1":"parser1",
                "attributename2":"parser2"
            },
            "property2":{
                "attributename1":"parser1",
                "attributename2":"parser2"
            }
        }
    }
     */
    public function toData($entityClass, $validates = [])
    {
        if (!class_exists($entityClass)) {
            throw new NotFoundClassException("Not Found Class:" . $entityClass);
        }

        //
        $reflect = new \ReflectionClass($entityClass);
        $data = $reflect->newInstance();

        //会有一个优先级 所有的参数都会通过  Safe 模式过滤
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        //数据会按 POST /GET 的顺序获取
        $needValidate = AttributeTool::hasAttribute($reflect, Validate::class);
        $errTip = [];
        /**
         * @var $property \ReflectionProperty
         */
        foreach ($properties as $property) {
            $name = $property->getName();
            $defVal = $property->getDefaultValue();
            $val = $this->getData($name);
            $val = $val === null ? $defVal : $val;
            //todo 优化
            if ($needValidate && ($tip = $this->validateData($validates, $entityClass, $name, $val))) {
                $tip = $this->validateData($validates, $entityClass, $name, $val);
                $errTip[$name] = $tip;
            }
            if ($val !== null && empty($errTip[$name])) {
                try {
                    $data->{$name} = $val;
                } catch (\TypeError $e) {
                    throw new \InvalidArgumentException($e->getMessage());
                }
            }
        }

        /**
         * 整理错误提示
         */
        $data->_errorTip = $errTip;
        $_errorStr = [];
        foreach($errTip as $val){
            $_errorStr = array_merge($_errorStr,$val);
        }
        $data->_errorTipStr = implode("\n",$_errorStr);
        return $data;
    }

    /**
     * @param $validates
    {
        "classname":{
            "property1":{
                "attributename1":"parser1",
                "attributename2":"parser2"
            },
            "property2":{
                "attributename1":"parser1",
                "attributename2":"parser2"
            }
        }
    }
     * @param $className
     * @param $propertyName
     * @param $val
     * @return array
     */
    private function validateData($validates, $className, $propertyName, $val)
    {
        $errorTip = [];
        if (isset($validates[$className][$propertyName])) {
            /**
             * @var $propertyAttribute PropertyData
             */
            foreach ($validates[$className][$propertyName] as $propertyAttribute) {
                /**
                 * @var $parser ValidateAttributeInterface
                 */
                $parser = $propertyAttribute->getParse();
                list($ret, $retVal) = $parser->check($val);
                if (!$ret) {
                    $errorTip[] = $retVal;
                }
            }
        }
        return $errorTip;
    }

    /**
     * 从 post/get中获取值 并且通过SAFE方法过滤
     * @param $key
     * @return array|string|null
     */
    private function getData($key)
    {
        $data = [];
        if ((Vitex::getInstance())->env->get('CONTENT_TYPE') == Mime::JSON) {
            $jsonData = file_get_contents('php://input');
            try {
                $jsonDataArr = json_decode($jsonData, true);
            } catch (Exception $e) {
                //出现异常的时候不会单独抛出直接处理
                $jsonDataArr = $_POST;
            }
            $data = $data + ($jsonDataArr ?? []);
        }
        $data = $data + ($_GET ?? []);
        $val = null;
        if (isset($data[$key])) {
            $val = $data[$key];
        }
        return $val === null ? $val : Filter::safe($val);
    }

}