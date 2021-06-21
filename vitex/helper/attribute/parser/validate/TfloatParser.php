<?php


namespace vitex\helper\attribute\parser\validate;


use vitex\core\attribute\validate\Tfloat;


/**
 * 浮点数解析器
 * @package vitex\helper\attribute\parser\validate
 */
class TfloatParser extends ValidateBaseParser implements ValidateAttributeInterface
{
    /**
     * 检查数字的有效性
     * @param $val
     * @return array
     */
    public function check($val):array
    {

        if (!is_float($val)) {
            return [false, $this->getmsg()];
        }
        /**
         * @var $instance Tfloat
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
         * @var $instance Tfloat
         */
        $instance = $instance ? $instance : $this->data->getAttributeInstance();
        $errmsg = '';
        if ($instance->getErrMsg() != Tfloat::ERROR_MSG) {
            //不是默认值说明有自定义就是用自定义
            $errmsg = $instance->getErrMsg();
        } else {
            if ($instance->getMin() == PHP_FLOAT_MIN && $instance->getMax() == PHP_FLOAT_MAX) {
                $errmsg = Tfloat::ERROR_MSG;
            } elseif ($instance->getMin() != PHP_FLOAT_MIN && $instance->getMax() != PHP_FLOAT_MAX) {
                $errmsg = Tfloat::ERROR_MSG_DOUBLE;
            } elseif ($instance->getMin() != PHP_FLOAT_MIN) {
                $errmsg = Tfloat::ERROR_MSG_MIN;
            } else {
                $errmsg = Tfloat::ERROR_MSG_MAX;
            }
        }

        return str_replace([
            '{attr}', '{min}', '{max}'
        ], [
            $instance->getFieldName(), $instance->getMin(), $instance->getMax()
        ], $errmsg);
    }
}