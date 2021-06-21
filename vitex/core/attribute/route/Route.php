<?php

namespace vitex\core\attribute\route;
use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\route\RouteParser;

/**
 * 请求的基本路径 和方法
 *
 * 如果当前注解 注解类对象则 method参数不会发挥任何作用
 * 如果注解方法对象则method参数发挥作用
 * Class RequestMapping
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Route implements AttributeInterface
{
    private string $path;

    private array $method;

    /**
     * @param $path string 路径
     */
    public function __construct(string $path, $method = "")
    {
        $this->path = $path;

        $this->method = is_array($method) ? $method : [$method];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getMethod(): array
    {
        return $this->method;
    }

    public function getParse(): AttributeParserInterface
    {
        return new RouteParser();
    }


}