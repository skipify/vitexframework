<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
namespace vitex\core;
use vitex\Vitex;

/**
 * 当前类用于重新组织环境变量
 */
class Env implements \ArrayAccess
{
    /**
     * 环境变量
     * @var array
     */
    private $_env;
    /**
     * 环境变量的实例
     */
    private static $_instance = null;

    /**
     * 可能会因为设置了分组而重新设置这个pathinfo信息
     */
    protected $_pathinfo = null;

    private function __construct()
    {
        //默认配置
        $default = [
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME'    => '',
            'PATH_INFO'      => '',
            'QUERY_STRING'   => '',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => 80,
            'vitex.params'   => array(),
        ];
        $this->_env = array_merge($default, $_SERVER);
    }

    /**
     * 单例，获取环境变量
     * @return self
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 获取变量配置
     * @param  string       $key        键值
     * @return array/string 返回值
     */
    public function get($key = null)
    {
        if ($key === null) {
            return $this->_env;
        }
        return $this->_env[$key] ?? '';
    }

    /**
     * 修改环境变量
     * @param  string $key 键值
     * @param  string $val 键名
     * @return self
     */
    public function set($key, $val)
    {
        $this->_env[$key] = $val;
        return $this;
    }

    /**
     * 设置获取请求方法
     * @param  string/null $method     请求方法
     * @return self        返回值
     */
    public function method($method = null)
    {
        if ($method === null) {
            return $this->get('REQUEST_METHOD');
        }
        $this->set("REQUEST_METHOD", $method);
        return $this;
    }

    /**
     * 一个兼容的pathinfo获取方法
     * 如果包含分组，这里要重写pathinfo的信息
     * @return array|null|string
     */
    public function getPathinfo()
    {
        //pathinfo已经被重写过
        if ($this->_pathinfo !== null) {
            return $this->_pathinfo;
        }
        $pathinfo = $this->get('PATH_INFO');
        if (!$pathinfo) {
            //兼容模式
            $vitex = Vitex::getInstance();
            if ($vitex->getConfig('router.compatible')) {
                $pathinfo = $_GET['u'] ?? '';
            }
        }
        return $pathinfo;
    }

    /**
     * 设置重写后的pathinfo信息
     * @param  $pathinfo
     * @return self
     */
    public function setPathinfo($pathinfo)
    {
        $this->_pathinfo = $pathinfo;
        return $this;
    }

    /**
     * Array Access
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->_env[$offset]);
    }

    public function offsetGet($offset)
    {
        if ($offset === null) {
            return null;
        }
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $offset              = strtoupper($offset);
        $this->_env[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        $offset = strtoupper($offset);
        unset($this->_env[$offset]);
    }

}
