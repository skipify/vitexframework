<?php

namespace vitex\core\attribute\validate;

use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\validate\EmailParser;

#[Slot]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class IsEmail implements AttributeInterface
{
    const ERROR_MSG = "{attr}必须为一个合法邮箱地址";
    public function __construct(
        private string $errMsg = self::ERROR_MSG,
        private string $fieldName = ''
    ){

    }

    public function getParse(): AttributeParserInterface
    {
        return new EmailParser();
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