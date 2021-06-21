<?php


namespace vitex\core\attribute\validate;


use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\validate\TstringParser;

#[Slot]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Tstring implements AttributeInterface
{
    const ERROR_MSG = "{attr}必须为一个字符串";

    const ERROR_MSG_DOUBLE = "{attr}长度为{min}和{max}之间";

    const ERROR_MSG_MIN = '{attr}长度需大于{min}个字符';

    const ERROR_MSG_MAX = '{attr}长度需小于{max}个字符';

    public function __construct(
        private int $min = 0,
        private int $max = PHP_INT_MAX,
        private string $errMsg = self::ERROR_MSG,
        private string $fieldName = ''
    )
    {

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



    public function getParse(): AttributeParserInterface
    {
        return new TstringParser();
    }
}