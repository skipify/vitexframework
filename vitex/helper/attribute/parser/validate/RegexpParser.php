<?php


namespace vitex\helper\attribute\parser\validate;


use vitex\core\attribute\validate\Regexp;


class RegexpParser extends ValidateBaseParser implements ValidateAttributeInterface
{

    /**
     * 解析
     * @param $val
     * @return array
     */
    public function check($val): array
    {
        /**
         * @var $instance Regexp
         */
        $instance = $this->data->getAttributeInstance();
        if (preg_match($instance->getPattern(), $val)) {
            return [true, $val];
        }
        return [false, $this->getErrMsg($val, $instance)];
    }
}