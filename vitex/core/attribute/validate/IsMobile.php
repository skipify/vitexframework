<?php


namespace vitex\core\attribute\validate;


use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\validate\MobileParser;

/**
 * 验证手机号
 * @package vitex\core\attribute\validate
 */
#[Slot]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class IsMobile implements AttributeInterface
{

    const ERROR_MSG = "{attr}不是合法手机号码";
    public function __construct(
        private string $errMsg = self::ERROR_MSG,
        private string $fieldName = ''
    ){

    }

    public function getParse(): AttributeParserInterface
    {
        return new MobileParser();
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