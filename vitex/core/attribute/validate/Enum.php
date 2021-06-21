<?php


namespace vitex\core\attribute\validate;

use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\validate\EnumParser;

/**
 * 枚举类型的判断
 * @package vitex\core\attribute\validate
 */
#[Slot]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Enum implements AttributeInterface
{
    const ERROR_MSG = "{attr}的值{val}不在允许的列表";

    public function __construct(
        private array $enums = [],
        private string $errMsg = self::ERROR_MSG,
        private string $fieldName = ''
    )
    {

    }

    public function getParse(): AttributeParserInterface
    {
        return new EnumParser();
    }

    /**
     * @return array
     */
    public function getEnums(): array
    {
        return $this->enums;
    }

    /**
     * @param array $enums
     */
    public function setEnums(array $enums): void
    {
        $this->enums = $enums;
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