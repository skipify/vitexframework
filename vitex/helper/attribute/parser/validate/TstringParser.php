<?php

namespace vitex\helper\attribute\parser\validate;


use vitex\core\attribute\validate\Tstring;


/**
 * 字符串解析器
 * @package vitex\helper\attribute\parser\validate
 */
class TstringParser extends ValidateBaseParser implements ValidateAttributeInterface
{
    /**
     * 检查数字的有效性
     * @param $val
     * @return array
     */
    public function check($val):array
    {
        $val = (string)$val;
        if (!is_string($val)) {
            return [false, $this->getmsg()];
        }
        /**
         *
         * @var $instance Tstring
         */
        $instance = $this->data->getAttributeInstance();
        if (mb_strlen($val, 'UTF-8') < $instance->getMin()) {
            return [false, $this->getmsg($instance)];
        }

        if (mb_strlen($val, 'UTF-8') > $instance->getMax()) {
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
         * @var $instance Tstring
         */
        $instance = $instance ? $instance : $this->data->getAttributeInstance();
        $errmsg = '';
        if ($instance->getErrMsg() != Tstring::ERROR_MSG) {
            //不是默认值说明有自定义就是用自定义
            $errmsg = $instance->getErrMsg();
        } else {
            if ($instance->getMin() == 0 && $instance->getMax() == PHP_INT_MAX) {
                $errmsg = Tstring::ERROR_MSG;
            } elseif ($instance->getMin() != 0 && $instance->getMax() != PHP_INT_MAX) {
                $errmsg = Tstring::ERROR_MSG_DOUBLE;
            } elseif ($instance->getMin() != 0) {
                $errmsg = Tstring::ERROR_MSG_MIN;
            } else {
                $errmsg = Tstring::ERROR_MSG_MAX;
            }
        }

        return str_replace([
            '{attr}', '{min}', '{max}'
        ], [
            $instance->getFieldName(), $instance->getMin(), $instance->getMax()
        ], $errmsg);
    }
}