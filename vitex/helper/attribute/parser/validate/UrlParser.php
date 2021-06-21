<?php


namespace vitex\helper\attribute\parser\validate;


use vitex\core\attribute\validate\IsUrl;


/**
 * 监测Url链接
 * @package vitex\helper\attribute\parser\validate
 */
class UrlParser extends ValidateBaseParser implements ValidateAttributeInterface
{
    /**
     * 监测返回2个参数，第一个为是否通过 第二个
     * 如果通过则返回参数值，如果没通过则返回错误信息
     * @param $val
     * @return array
     */
    public function check($val): array
    {
        /**
         * @var $instance IsUrl
         */
        $instance = $this->data->getAttributeInstance();
        if (!Validate::isUrl($val)) {
            $errmsg = $this->getErrMsg($val, $instance);
            return [false, $errmsg];
        }
        return [true, $val];
    }
}