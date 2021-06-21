<?php


namespace vitex\helper\attribute\parser\validate;


use vitex\helper\attribute\parser\AttributeParserInterface;

interface ValidateAttributeInterface extends AttributeParserInterface
{
    /**
     * 验证字段是否符合要求
     * @param $val
     * @return array
     */
    public function check($val): array;
}