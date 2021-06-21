<?php


namespace vitex\helper\attribute\parsedata;

use vitex\helper\attribute\parser\ParseDataBase;
use vitex\helper\attribute\parser\ParseDataInterface;

/**
 * 记录解析的路由信息
 * @package vitex\helper\attribute\parser\route
 */
class RouteMapData extends ParseDataBase implements ParseDataInterface
{


    /**
     * 如果注解来自于类 则是 true
     * @var
     */
    private bool $isClassData = false;
    /**
     * 路由的基类
     * @var
     */
    private string $className;
    /**
     * 请求的方法名字
     * @var string
     */
    private string $methodName;

    /**
     * 访问路径
     * 如果  isClassData=1 则表示是基础的路径   内部所有的方法都要拼接这一段
     * @var
     */
    private string $path;
    /**
     * 请求方法
     * @var
     */
    private array $method;



    /**
     * @return mixed
     */
    public function getIsClassData(): bool
    {
        return $this->isClassData;
    }

    /**
     * @param mixed $isClassData
     */
    public function setIsClassData(string $isClassData): void
    {
        $this->isClassData = $isClassData;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path): void
    {
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method): void
    {
        $method = is_array($method) ? $method : [$method];
        $this->method = $method;
    }

    /**
     * @return mixed
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @param mixed $className
     */
    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @param string $methodName
     */
    public function setMethodName(string $methodName): void
    {
        $this->methodName = $methodName;
    }


}