<?php


namespace vitex\helper\attribute\parser\validate;


use vitex\core\attribute\validate\IsIdCard;
use vitex\core\attribute\validate\IsMobile;
use vitex\helper\attribute\parser\AttributeParserInterface;

/**
 * 验证手机号
 * @package vitex\helper\attribute\parser\validate
 */
class MobileParser extends ValidateBaseParser implements AttributeParserInterface
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
         * @var $instance IsMobile
         */
        $instance = $this->data->getAttributeInstance();
        if (!Validate::isMobile($val)) {
            $errmsg = $this->getErrMsg($val, $instance);
            return [false, $errmsg];
        }
        return [true, $val];
    }

}