<?php
namespace  vitex\core\attribute\route;
use vitex\constant\RequestMethod;
use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\route\DeleteParser;
use vitex\helper\attribute\parser\route\RouteParser;

/**
 * delete请求
 * @package vitex\core\attribute\route
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Delete implements AttributeInterface
{
    private string $path;

    private array $method;

    /**
     * @param $path string 路径
     */
    public function __construct(string $path)
    {
        $this->path = $path;

        $this->method = [RequestMethod::DELETE];
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
        return new DeleteParser();
    }
}