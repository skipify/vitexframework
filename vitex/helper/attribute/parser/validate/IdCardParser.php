<?php


namespace vitex\helper\attribute\parser\validate;


use vitex\core\attribute\validate\IsIdCard;


/**
 * 监测身份证号码
 * @package vitex\helper\attribute\parser\validate
 */
class IdCardParser extends ValidateBaseParser implements ValidateAttributeInterface
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
         * @var $instance IsIdCard
         */
        $instance = $this->data->getAttributeInstance();
        if (!Validate::isIdCard($val)) {
            $errmsg = $this->getErrMsg($val, $instance);
            return [false, $errmsg];
        }
        return [true, $val];
    }
}