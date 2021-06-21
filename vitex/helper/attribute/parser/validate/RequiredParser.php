<?php

namespace vitex\helper\attribute\parser\validate;

use vitex\core\attribute\validate\Required;

/**
 * 解析必填的值
 * @package vitex\helper\attribute\parser\validate
 */
class RequiredParser extends ValidateBaseParser
    implements ValidateAttributeInterface
{
    /**
     * 被标记为空的元素
     * @var array
     */
    private array $emptyList = ['', null, false, []];

    /**
     * 监测返回2个参数，第一个为是否通过 第二个
     * 如果通过则返回参数值，如果没通过则返回错误信息
     * @param $val
     * @return array
     */
    public function check($val): array
    {
        /**
         * @var $instance Required
         */
        $instance = $this->data->getAttributeInstance();

        foreach ($this->emptyList as $_val) {
            if ($_val === $val) {
                //为空了
                $errmsg = $this->getErrMsg($val, $instance);
                return [false, $errmsg];
            }
        }
        return [true, $val];
    }


}