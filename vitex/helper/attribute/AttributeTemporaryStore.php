<?php


namespace vitex\helper\attribute;

use vitex\helper\traits\SetTrait;

/**
 * 此类是一个单例
 * 临时暂存一些解析的注解相关数据
 * 数据一般用于Slot类型的注解 单独使用
 * 比如模型加载时配置的数据可以在加载模型的时候使用 而不是统一把所有模型一次性加载 提高加载效率
 * @package vitex\helper\attribute
 */
class AttributeTemporaryStore
{
    /**
     * Table注解的键值
     */
    const TABLE = "table";

    /**
     * 路由类注解缓存
     */
    const CLASS_ROUTE = "class_route";


    private static $_instance;

    /**
     * 数据存储
     * @var array
     */
    private array $data;

    private function __construct()
    {

    }

    public static function instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 初始化一个数组如果已经存在则不覆盖
     * @param string $key
     * @param array $val
     * @return $this
     */
    public function add(string $key, array $val)
    {
        if (isset($this->data[$key])) {
            $this->data[$key] = array_merge($this->data[$key], $val);
        } else {
            $this->data[$key] = $val;
        }
        return $this;
    }

    /**
     * 初始化一个数组如果已经存在则不覆盖
     * @param string $subKey
     * @return $this
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * 获取缓存子内容
     * @param string $key
     * @param string $subKey
     * @return null
     */
    public function getSub(string $key, string $subKey)
    {
        $data = $this->get($key);
        return $data == null ? null : (isset($data[$subKey]) ? $data[$subKey] : null);
    }

//    public function __set($key, $val)
//    {
//        $this->data[$key] = $val;
//    }
//
//    public function __get($key)
//    {
//        return $this->data[$key] ?? null;
//    }

}