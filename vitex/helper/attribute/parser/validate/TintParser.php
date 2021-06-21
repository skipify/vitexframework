<?php


namespace vitex\helper\attribute\parser\validate;


use vitex\core\attribute\validate\Tint;

/**
 * 整形解析器
 * @package vitex\helper\attribute\parser\validate
 */
class TintParser extends ValidateBaseParser implements ValidateAttributeInterface
{
    /**
     * 检查数字的有效性
     * @param $val
     * @return array
     */
    public function check($val):array
    {
        if (!is_numeric($val)) {
            return [false, $this->getmsg()];
        }
        /**
         * @var $instance Tint
         */
        $instance = $this->data->getAttributeInstance();
        if ($val < $instance->getMin()) {
            return [false, $this->getmsg($instance)];
        }

        if ($val > $instance->getMax()) {
            return [false, $this->getmsg($instance)];
        }

        return [true, $val];
    }

    /**
     * 错误信息
     * @return string|string[]
     */
    private function getmsg($instance = null)
    {
        /**
         * @var $instance Tint
         */
        $instance = $instance ? $instance : $this->data->getAttributeInstance();
        $errmsg = '';
        if ($instance->getErrMsg() != Tint::ERROR_MSG) {
            //不是默认值说明有自定义就是用自定义
            $errmsg = $instance->getErrMsg();
        } else {
            if ($instance->getMin() == PHP_INT_MIN && $instance->getMax() == PHP_INT_MAX) {
                $errmsg = Tint::ERROR_MSG;
            } elseif ($instance->getMin() != PHP_INT_MIN && $instance->getMax() != PHP_INT_MAX) {
                $errmsg = Tint::ERROR_MSG_DOUBLE;
            } elseif ($instance->getMin() != PHP_INT_MIN) {
                $errmsg = Tint::ERROR_MSG_MIN;
            } else {
                $errmsg = Tint::ERROR_MSG_MAX;
            }
        }

        return str_replace([
            '{attr}', '{min}', '{max}'
        ], [
            $instance->getFieldName(), $instance->getMin(), $instance->getMax()
        ], $errmsg);
    }
}