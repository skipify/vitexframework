<?php declare(strict_types=1);
/**
 * Vitex 一个基于php7.0开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
namespace vitex\core;

use vitex\ext\Filter;
use vitex\helper\Set;
use vitex\helper\SetMethod;

/**
 * 所有请求方法的类对象，包含所有的Query string POST DATA Cookie等
 */
class Request implements \ArrayAccess, \Iterator
{

    //环境变量
    public  $uploadError;
    private $env, $isstrip = false;
    //当前实例
    private static $_instance = null;

    /**
     * 保存当前匹配的基本路由信息
     *
     */
    public $route = [];
    use SetMethod;
    private $methods = []; //扩展的方法

    /**
     * @var Set URL中的分段信息
     */
    public $params;
    /**
     * @var Set post/delete提交的信息 $_POST
     */
    public $body;
    /**
     * @var Set get方式传递的信息 $_GET
     */
    public $query;
    /**
     * @var Set 上传的文件信息 $_FILES
     */
    public $files;
    /**
     * @var Set cookie信息 $_COOKIE
     */
    public $cookies;
    /**
     * @var Set session信息 $_SESSION
     */
    public $session;
    /**
     * @var string IP地址
     */
    public $ip;
    /**
     * @var string host
     */
    public $hostname;
    /**
     * @var string referer
     */
    public $referer, $referrer;

    /**
     * @var bool
     */
    public $isAjax, $isXhr;

    /**
     * @var string
     */
    public $protocol, $version, $secure;

    private function __construct()
    {
        $this->env = Env::getInstance();
        //初始化数据
        $this->queryData()
            ->postData()
            ->fileData()
            ->parseReq();
        $this->isAjax = $this->isAjax();
        $this->isXhr = $this->isAjax;
    }

    /**
     * 获取实例的单例
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
     * 解析一些常见的请求信息
     * @return self
     */
    private function parseReq()
    {
        $this->ip = $this->getIp();
        $this->hostname = $this->env->get('HTTP_HOST');
        $this->referrer = $this->env->get('HTTP_REFERER');
        $this->referer = $this->referrer;
        //请求协议
        $protocol = $this->env->get('SERVER_PROTOCOL');
        //设置变量
        if($protocol){
            list($this->protocol, $this->version) = explode('/', $protocol);
            if ($this->protocol == 'https') {
                $this->secure = true;
            } else {
                $this->secure = false;
            }
        } else {
            $this->secure = false;
        }

        return $this;
    }

    /**
     * 获取IP
     * @return string
     */
    private function getIp()
    {
        $ip = $this->env->get("HTTP_CLIENT_IP");
        $ip = $ip ?: $this->env->get("HTTP_X_FORWARDED_FOR");
        $ip = $ip ?: $this->env->get("REMOTE_ADDR");
        if($ip){
            $ipInt = ip2long($ip);
            if(is_integer($ipInt)){
                $ip = long2ip($ipInt);
            }
        }
        return $ip;
    }


    /**
     *
     * 此时对于 ' " \ 会自动增加\转义
     * 如果您要获取到最原始的转义前的数据可以使用 $_POST  $_GET来获取
     * 如果要在当前runtime中使用 $req->body $req->query来获取非转义的原始数据,你需要执行此方法
     * 执行此方法后会自动去除默认转义的字符
     * @return $this
     */
    public function cancelFilter()
    {
        if ($this->isstrip) {
            return $this;
        }
        if (strtolower($this->env->get('REQUEST_METHOD')) == 'put' || strtolower($this->env->get('REQUEST_METHOD')) == 'delete') {
            //put方法
            $body = file_get_contents('php://input');
            $bodys = [];
            parse_str($body, $bodys);
            $_POST = array_merge($bodys, $_POST);
        }
        $this->body->import($_POST);
        $this->query->import($_GET);
        return $this;
    }

    /**
     * @deprecated
     */
    public function setNotFilter()
    {
        return $this->cancelFilter();
    }

    /**
     * 设置全局过滤方法
     * @param $filter
     * @return $this
     */
    public function setFilter($filter)
    {
        if (strtolower($this->env->get('REQUEST_METHOD')) == 'put' || strtolower($this->env->get('REQUEST_METHOD')) == 'delete') {
            //put方法
            $body = file_get_contents('php://input');
            $bodys = [];
            parse_str($body, $bodys);
            $_POST = array_merge($bodys, $_POST);
        }
        $this->body->import(Filter::factory($_POST, $filter));
        $this->query->import(Filter::factory($_GET, $filter));
        return $this;
    }

    /**
     * 解析请求query string
     * @return self
     */
    private function queryData()
    {
        $data = $_GET;
        $this->query = new Set($data);
        return $this;
    }

    /**
     * 解析body信息 即 post的信息
     * @return self
     */
    private function postData()
    {
        if (strtolower($this->env->get('REQUEST_METHOD')) == 'put' || strtolower($this->env->get('REQUEST_METHOD')) == 'delete') {
            //put方法
            $body = file_get_contents('php://input');
            $bodys = [];
            parse_str($body, $bodys);
            $_POST = array_merge($bodys, $_POST);
        }
        $data = $_POST;
        $this->body = new Set($data);
        return $this;
    }

    /**
     * 上传的文件信息
     * @return self
     */
    private function fileData()
    {
        $this->files = new Set($_FILES);
        return $this;
    }

    /**
     * 获取server变量的信息
     * @param string $key 键值
     * @return array
     */
    public function getEnv($key)
    {
        $key = strtoupper($key);
        return $this->env->get($key);
    }

    /**
     * 获取单个请求值，获取的顺序为  params > query > body
     * @param  string $key 要获取的键值
     * @param  string $def 当此值获取不存在时的返回值
     * @param string $filter 过滤方法
     * @return mixed  返回值
     */
    public function get($key, $def = "", $filter = null)
    {
        $val = $this->getParam($key, null, $filter);
        $val = $val === null ? $this->getQuery($key, null, $filter) : $val;
        $val = $val === null ? $this->getBody($key, null, $filter) : $val;
        return $val === null ? $def : $val;
    }


    /**
     * 获取 post/put等设置的body的内容
     * @param $key
     * @param string $def
     * @param null|string $filter
     * @return null|string
     * @throws Exception
     */
    public function getBody($key, $def = "", $filter = null)
    {
        $val = $this->body[$key];
        if ($filter) {
            $val = Filter::factory($val, $filter);
        }
        return $val === null ? $def : $val;
    }

    /**
     * 从URL分段信息中获取值
     * @param $key
     * @param string $def
     * @param null $filter
     * @return mixed|null|string
     * @throws Exception
     */
    public function getParam($key, $def = "", $filter = null)
    {
        $val = $this->params[$key];
        if ($filter) {
            $val = Filter::factory($val, $filter);
        }
        return $val === null ? $def : $val;
    }

    /**
     * 从query字符中获取值,也就是获取$_GET的值
     * @param $key
     * @param string $def
     * @param null|string $filter
     * @return null|string
     * @throws Exception
     */
    public function getQuery($key, $def = "", $filter = null)
    {
        $val = $this->query[$key];
        if ($filter) {
            $val = Filter::factory($val, $filter);
        }
        return $val === null ? $def : $val;
    }

    /**
     * 根据数组获取相应的内容
     * @param  array $arr 数组值，每个值都是一个表单元素
     * @param  string $filter 过滤方式
     * @return array 返回值
     */
    public function getData(array $arr, $filter = null)
    {
        $data = [];
        foreach ($arr as $val) {
            $data[$val] = $this->body->{$val};
            if ($filter) {
                $data[$val] = Filter::factory($data[$val], $filter);
            }
        }
        return $data;
    }

    /**
     * 从post中获取内容，排除指定的字段
     * @param $fields mixed 一个字段或者数组的多个字段
     * @param null $filter
     * @return array
     */
    public function except($fields, $filter = null)
    {
        $fields = !is_array($fields) ? [$fields] : $fields;
        $bodyData = $this->body->all();
        $data = [];
        foreach ($bodyData as $key => $val) {
            if (in_array($key, $fields)) {
                continue;
            }
            $data[$key] = $filter ? Filter::factory($data[$val], $filter) : $data[$key];
        }
        return $data;
    }

    /**
     * 是否是get请求方法
     * @return bool
     */
    public function isGet()
    {
        return strtolower($this->env->method()) == 'get';
    }

    /**
     * 是否是post请求方法
     * @return bool
     */
    public function isPost()
    {
        return strtolower($this->env->method()) == 'post';
    }

    /**
     * 是否是put请求方法
     *
     * @return bool
     */
    public function isPut()
    {
        return strtolower($this->env->method()) == 'put';
    }

    /**
     * 是否是patch请求方法
     *
     * @return bool
     */
    public function isPatch()
    {
        return strtolower($this->env->method()) == 'patch';
    }

    /**
     * 是否是delete请求方法
     *
     * @return bool
     */
    public function isDelete()
    {
        return strtolower($this->env->method()) == 'delete';
    }

    /**
     * 是否是head请求方法
     *
     * @return bool
     */
    public function isHead()
    {
        return strtolower($this->env->method()) == 'head';
    }

    /**
     * 是否是options请求方法
     *
     * @return bool
     */
    public function isOptions()
    {
        return strtolower($this->env->method()) == 'options';
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
     * 扩展方法,扩展的如果是类方法必须至少包含一个参数,第一个参数总是当前这个类的实例
     * 例如 function($obj){$obj->extend('a','1');}//第一个参数即为当前类的实例
     *
     * @param  mixed $pro 扩展的属性名或者方法名,或者一个关联数组
     * @param  string /null $data 属性值或者一个callable的方法
     * @return self
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
            $this->methods[$pro] = \Closure::bind($data, $this, 'Request');
        } else {
            $this->{$pro} = $data;
        }
        return $this;
    }

    /**
     * 执行调用扩展的方法
     * @param  string $method 扩展的方法名
     * @param  mixed $args 参数名
     * @throws Exception
     * @return self
     */
    public function __call($method, $args)
    {
        if (!isset($this->methods[$method])) {
            throw new Exception('Not Method ' . $method . ' Found In Request!',Exception::CODE_NOTFOUND_METHOD);
        }
        return call_user_func_array($this->methods[$method], $args);
    }
}
