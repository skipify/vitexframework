<?php


namespace vitex\helper\attribute\parser\validate;


use vitex\core\attribute\validate\IsEmail;


/**
 * 监测邮箱
 * @package vitex\helper\attribute\parser\validate
 */
class EmailParser extends ValidateBaseParser implements ValidateAttributeInterface
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
         * @var $instance IsEmail
         */
        $instance = $this->data->getAttributeInstance();
        if (!Validate::isEmail($val)) {
            $errmsg = $this->getErrMsg($val, $instance);
            return [false, $errmsg];
        }
        return [true, $val];
    }
}