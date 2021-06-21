<?php


namespace vitex\core\attribute\validate;

use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\validate\TintParser;

/**
 * 整形数据
 * @package vitex\core\attribute\validate
 */
#[Slot]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Tint implements AttributeInterface
{

    const ERROR_MSG = "{attr}必须为一个整数";

    const ERROR_MSG_DOUBLE = "{attr}必须为{min}和{max}之间的整数";

    const ERROR_MSG_MIN = '{attr}必须为大于{min}的整数';

    const ERROR_MSG_MAX = '{attr}必须为小于{max}的整数';

    public function __construct(
        private int $min = PHP_INT_MIN,
        private int $max = PHP_INT_MAX,
        private string $errMsg = self::ERROR_MSG,
        private string $fieldName = ''
    )
    {

    }

    public function getParse(): AttributeParserInterface
    {
        return new TintParser();
    }

    /**
     * @return int
     */
    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * @param int $min
     */
    public function setMin(int $min): void
    {
        $this->min = $min;
    }

    /**
     * @return int
     */
    public function getMax(): int
    {
        return $this->max;
    }

    /**
     * @param int $max
     */
    public function setMax(int $max): void
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