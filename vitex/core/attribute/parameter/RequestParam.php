<?php


namespace vitex\core\attribute\parameter;

use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\parameter\RequestParamParser;

/**
 * 单个参数注解，如果没有传递该参数则会使用默认值传递，默认值根据不同类型设置，可以参看 AtrtibuteTool::defVal 查看默认值
 * @package vitex\core\attribute\parameter
 */
#[Slot]
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class RequestParam implements AttributeInterface
{
    public function __construct(private string $key = '')
    {

    }

    public function getParse(): AttributeParserInterface
    {
        return new RequestParamParser();
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }


}