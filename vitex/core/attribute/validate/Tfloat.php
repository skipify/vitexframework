<?php


namespace vitex\core\attribute\validate;

use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\validate\TfloatParser;
use vitex\helper\attribute\parser\validate\TintParser;

/**
 * 浮点数据
 * @package vitex\core\attribute\validate
 */
#[Slot]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Tfloat implements AttributeInterface
{

    const ERROR_MSG = "{attr}必须为一个浮点数";

    const ERROR_MSG_DOUBLE = "{attr}必须为{min}和{max}之间的浮点数";

    const ERROR_MSG_MIN = '{attr}必须为大于{min}的浮点数';

    const ERROR_MSG_MAX = '{attr}必须为小于{max}的浮点数';

    public function __construct(
        private float $min = PHP_FLOAT_MIN,
        private float $max = PHP_FLOAT_MAX,
        private string $errMsg = self::ERROR_MSG,
        private string $fieldName = ''
    )
    {

    }

    public function getParse(): AttributeParserInterface
    {
        return new TfloatParser();
    }

    /**
     * @return float
     */
    public function getMin(): float
    {
        return $this->min;
    }

    /**
     * @param float $min
     */
    public function setMin(float $min): void
    {
        $this->min = $min;
    }

    /**
     * @return float
     */
    public function getMax(): float
    {
        return $this->max;
    }

    /**
     * @param float $max
     */
    public function setMax(float $max): void
    {
        $this->max = $max;
    }

    /**
     * @return string
     */
    public function getErrMsg(): string
    {
        return $this->errMsg;
    }

    /**
     * @param string $errMsg
     */
    public function setErrMsg(string $errMsg): void
    {
        $this->errMsg = $errMsg;
    }

    /**
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     */
    public function setFieldName(string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }


}