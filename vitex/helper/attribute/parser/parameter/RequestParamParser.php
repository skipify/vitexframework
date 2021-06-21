<?php

namespace vitex\helper\attribute\parser\parameter;


use vitex\constant\Mime;
use vitex\core\attribute\parameter\RequestParam;
use vitex\core\Env;
use vitex\core\Exception;
use vitex\core\Request;
use vitex\ext\Filter;
use vitex\helper\attribute\parsedata\ParameterData;
use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\service\http\Cookie;
use vitex\service\http\MultipartFile;
use vitex\Vitex;

/**
 * 解析RequestBody注解
 * 只对 public 修饰的属性才可以用
 * @package vitex\helper\attribute\parser\parameter
 */
class RequestParamParser extends AttributeParserBase implements AttributeParserInterface
{
    private ParameterData $data;

    public function parse(\ReflectionAttribute $attribute, $instance = null, $reflectInstance = null)
    {
        /**
         * @var $instance RequestParam
         */
        $instance = $instance ? $instance : $attribute->newInstance();

        /**
         * @var $type \ReflectionNamedType
         */
        $type = $reflectInstance?->getType();


        $parameterData = new ParameterData();
        $parameterData->setParse($this);
        $parameterData->setParameterName($instance->getKey() ? $instance->getKey() : $reflectInstance?->getName());
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
     */
    public function toData($key)
    {
        return $this->getData($key);
    }

    /**
     * 从 post/get中获取值 并且通过SAFE方法过滤
     * @param $key
     * @return array|string|null
     */
    private function getData($key)
    {
        $vitex = Vitex::getInstance();

        /**
         * 一些特殊注入的类型，File
         */
        if ($this->data->getParameterType() == MultipartFile::class) {
            return $vitex->req->ufile[$this->data->getParameterName()];
        }

        $data = [];
        if ($vitex->env->get('CONTENT_TYPE') == Mime::JSON) {
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