<?php


namespace vitex\core\attribute\sys;

use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\sys\CacheParser;

/**
 * 缓存下来的数据  在当前runtime环境有效  不能跨请求使用
 * 函数或者方法返回的数据会被缓存下来，下次调用的时候直接返回结果
 * 不要缓存bool值类型
 * 要使用此注解 需要安装 runkit7扩展，此扩展用于动态修改代码
 * @package vitex\core\attribute\sys
 */
#[Slot]
#[\Attribute(\Attribute::TARGET_METHOD)]
class Caching implements AttributeInterface
{
    public function __construct(
    )
    {

    }

    public function getParse(): AttributeParserInterface
    {
        return new CacheParser();
    }


}