<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package Vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
namespace Vitex\Core;

/**
 * 所有请求方法的类对象，包含所有的Query string POST DATA Cookie等
 */
class Request implements \ArrayAccess, \Iterator
{

    //环境变量
    private $env;
    //当前实例
    private static $_instance = null;

    /**
     * 保存基本的路由信息
     *
     */
    public $route = [];
    use \Vitex\Helper\SetMethod;
    private $methods = []; //扩展的方法

    private function __construct()
    {
        $this->env = Env::getInstance();
        //初始化数据
        $this->queryData()
            ->postData()
            ->fileData()
            ->parseReq();
        $this->isAjax = $this->isAjax();
        $this->isXhr  = $this->isAjax;
    }

    /**
     * 获取实例的单例
     * @return [type] [description]
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 解析一些常见的请求信息
     * @return object $this
     */
    public function parseReq()
    {
        $this->ip       = $this->env->get('REMOTE_ADDR');
        $this->hostname = $this->env->get('HTTP_HOST');
        $this->referrer = $this->env->get('HTTP_REFERER');
        $this->referer  = $this->referrer;
        //请求协议
        $protocol = $this->env->get('SERVER_PROTOCOL');
        //设置变量
        list($this->protocol, $this->version) = explode('/', $protocol);
        if ($this->protocol == 'https') {
            $this->secure = true;
        } else {
            $this->secure = false;
        }
        return $this;
    }

    /**
     * 解析请求query string
     * @return object
     */
    public function queryData()
    {
        //todo: clear params
        $this->query = new \Vitex\Helper\Set($_GET);
        return $this;
    }

    /**
     * 解析body信息 即 post的信息
     */
    public function postData()
    {
        if (strtolower($this->env->get('REQUEST_METHOD')) == 'put' || strtolower($this->env->get('REQUEST_METHOD')) == 'delete') {
            //put方法
            $body  = file_get_contents('php://input');
            $bodys = [];
            parse_str($body, $bodys);
            $_POST = array_merge($bodys, $_POST);
        }
        $this->body = new \Vitex\Helper\Set($_POST);
        return $this;
    }

    /**
     * 上传的文件信息
     * @return object
     */
    public function fileData()
    {
        $this->files = new \Vitex\Helper\Set($_FILES);
        return $this;
    }

    /**
     * 获取server变量的信息
     * @param string $key 键值
     */
    public function getEnv($key)
    {
        $key = strtoupper($key);
        return $this->env->get($key);
    }
    /**
     * 获取单个请求值，获取的顺序为  params > query > body
     * @param  string $key        要获取的键值
     * @param  string $def        当此值获取不存在时的返回值
     * @return mixed  返回值
     */
    public function get($key, $def = "")
    {
        $val = null;
        if ($val = $this->params->{$key}) {
            return $val;
        } elseif (isset($_GET[$key])) {
            return $_GET[$key];
        } elseif (isset($_POST[$key])) {
            return $_POST[$key];
        }
        return $def;
    }

    /**
     * 判断请求是不是XHR请求
     * @return boolean
     */
    public function isAjax()
    {
        if ($this->env->get('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest') {
            return true;
        }
        return false;
    }

    /**
     * 根据数组获取相应的内容
     * @param  array $arr        数组值，每个值都是一个表单元素
     * @return array 返回值
     */
    public function getData(array $arr)
    {
        $data = [];
        foreach ($arr as $val) {
            $data[$val] = $this->body->{$val};
        }
        return $data;
    }
    /**
     * 扩展方法,扩展的如果是类方法必须至少包含一个参数,第一个参数总是当前这个类的实例
     * 例如 function($obj){$obj->extend('a','1');}//第一个参数即为当前类的实例
     *
     * @param  mixed       $pro    扩展的属性名或者方法名,或者一个关联数组
     * @param  string/null $data   属性值或者一个callable的方法
     * @return object      $this
     */
    public function extend($pro, $data = null)
    {
        if (is_array($pro)) {
            foreach ($pro as $k => $v) {
                $this->extend($k, $v);
            }
            return $this;
        }
        if (is_callable($data)) {
            $this->methods[$pro] = Closure::bind($data, $this, 'Request');
        } else {
            $this->{$pro} = $data;
        }
        return $this;
    }

    /**
     * 执行调用扩展的方法
     * @param  string $method 扩展的方法名
     * @param  mixed  $args   参数名
     * @return object $this
     */
    public function __call($method, $args)
    {
        if (!isset($this->methods[$method])) {
            throw new \Exception('Not Method ' . $method . ' Found In Request!');
        }
        return call_user_func_array($this->methods[$method], $args);
    }
}
