<?php

namespace vitex\helper\attribute\parser\route;

use vitex\constant\RequestMethod;
use vitex\core\attribute\route\Route;
use vitex\helper\attribute\AttributeTemporaryStore;
use vitex\helper\attribute\parsedata\RouteMapData;
use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\ParseDataInterface;
use vitex\Vitex;

/**
 * 解析路由基类
 * @package vitex\helper\attribute\parser\route
 */
class RouteParser extends AttributeParserBase implements AttributeParserInterface
{

    const CACHE_FILE = "request.attribute.php";
    /**
     * 当前注解的数据
     * @var RouteMapData
     */
    protected RouteMapData $data;

    protected string $cachePath;

    public function __construct()
    {
        $vitex = Vitex::getInstance();
        $this->cachePath = $vitex->getConfig("runtime_dir");
    }

    /**
     * 解析
     * @param \ReflectionAttribute $attribute
     * @param null $instance
     * @param null $reflectInstance
     * @return ParseDataInterface
     */
    public function parse(\ReflectionAttribute $attribute, $instance = null, $reflectInstance = null): ParseDataInterface
    {
        /**
         * @var $instance Route
         */
        $instance = $instance ? $instance : $attribute->newInstance();

        $requestMapData = new RouteMapData();
        /**
         * 设置当前解析器一个引用
         */
        $requestMapData->setParse($this);//
        $requestMapData->setAttribute($attribute);
        if ($attribute->getTarget() == \Attribute::TARGET_CLASS) {
            $requestMapData->setIsClassData(true);
        }
        $requestMapData->setPath($instance->getPath());
        if (!$instance->getMethod()) {
            $requestMapData->setMethod(RequestMethod::ALL);
        } else {
            $requestMapData->setMethod($instance->getMethod());
        }
        $this->data = $requestMapData;
        @unlink($this->cachePath . '/' . self::CACHE_FILE);
        return $requestMapData;
    }

    /**
     * 最终要解析所有整理好的注解
     */
    public function doFinal(array $attributes)
    {
        /**
         * 类注解 则不作任何事情
         */
        if ($this->data->getIsClassData()) {
            return;
        }

        //解析方法路由
        $basePath = $this->getBaseClassAttribute($this->data->getAttributedClassName());
        $path = $basePath . $this->data->getPath();
        $call = $this->data->getAttributedClassName() . '@' . $this->data->getAttributedMethodName();
        $data = "";
        $vitex = Vitex::getInstance();
        foreach ($this->data->getMethod() as $method) {
            //$method = strtolower($method);
            $data .= '$vitex->' . $method . '("' . $path . '","' . $call . '");' . "\n";
            $vitex->{$method}($path, $call);
        }
        $this->writeFile(self::CACHE_FILE, $data);
    }

    /**
     * 获取注解到类的路径
     * @param $className
     * @return mixed|string
     */
    protected function getBaseClassAttribute($className)
    {
        if ($ret = AttributeTemporaryStore::instance()->getSub(AttributeTemporaryStore::CLASS_ROUTE, $className)) {
            return $ret;
        }
        $classReflect = new \ReflectionClass($className);
        $attributes = $classReflect->getAttributes(Route::class);

        $basePath = "";
        /**
         * @var $attribute \ReflectionAttribute
         */
        foreach ($attributes as $attribute) {
            $args = $attribute->getArguments();
            $basePath = $args[0];
        }
        AttributeTemporaryStore::instance()->add(AttributeTemporaryStore::CLASS_ROUTE, [$className => $basePath]);
        return $basePath;
    }

    /**
     * 路由文件写入文件
     * @param $file
     * @param $data
     */
    protected function writeFile($file, $data)
    {
        $file = $this->cachePath . '/' . $file;
        if (!file_exists($file)) {
            file_put_contents($file, '<' . '?php' . "\n");
        }
        file_put_contents($file, $data, FILE_APPEND);
    }
}