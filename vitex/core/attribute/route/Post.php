<?php
namespace  vitex\core\attribute\route;
use vitex\constant\RequestMethod;
use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\route\PostParser;
use vitex\helper\attribute\parser\route\RouteParser;

/**
 * post请求
 * @package vitex\core\attribute\route
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Post implements AttributeInterface
{
    private string $path;

    private array $method;

    /**
     * @param $path string 路径
     */
    public function __construct(string $path)
    {
        $this->path = $path;

        $this->method = [RequestMethod::POST];
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
        return new PostParser();
    }
}