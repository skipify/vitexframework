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
 * 用于发送请求的管理类，主要用于输出数据
 */
class Response
{
    private static $_instance = null;
    private $methods          = [];

    protected static $status_tip = array(
        //信息 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        //成功 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        //跳转 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
        //找不到 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        418 => '418 I\'m a teapot',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',
        //错误 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported',
    );

    public $status   = 200;
    private $headers = [];
    public $locals; //当前变量值，用于保存一次请求的变量 set的实例
    private function __construct()
    {
        $this->locals = new \Vitex\helper\Set();
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
     * 设置模板中的数据可以设置单个也可以设置多个
     * @param  string $key    键值
     * @param  string $val    内容
     * @return object $this
     */
    public function set($key, $val = null)
    {
        if (is_array($key)) {
            $this->locals = array_merge($this->locals, $key);
        } else {
            $this->locals[$key] = $val;
        }
        return $this;
    }

    /**
     * 输出json格式的内容
     * @param  mixed    $arr 要输出的内容
     * @param  boolean  $out 是否输出 默认为true 设置为false的时候返回编码好的数据
     * @return string
     */
    public function json($arr, $out = true)
    {
        $res = json_encode($arr, JSON_UNESCAPED_UNICODE);
        if ($out) {
            echo $res;
        } else {
            return $res;
        }
    }

    /**
     * 输出jsonp格式的内容
     * @param  mixed    $arr      要输出的内容
     * @param  mixed    $callback 回调函数名，不指定则自动根据配置获取
     * @param  boolean  $out      是否输出 默认为true 设置为false的时候返回编码好的数据
     * @return string
     */
    public function jsonp($arr, $callback = '', $out = true)
    {
        if (!$callback) {
            $vitex    = \Vitex\Vitex::getInstance();
            $key      = $vitex->getConfig('callback');
            $callback = $vitex->req->query[$key];
        }
        $res = json_encode($arr, JSON_UNESCAPED_UNICODE);
        $res = $callback . '(' . $res . ');';
        if ($out) {
            echo $res;
        } else {
            return $res;
        }
    }

    /**
     * 设置请求头信息
     * @param string $key 键值
     * @param strng  $val 键名
     */
    public function setHeader($key, $val)
    {
        $this->headers[$key] = $val;
        return $this;
    }

    /**
     * 获取请求头信息
     * @param  string         $key 键值
     * @return array/string
     */
    public function getHeader($key = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        return isset($this->headers[$key]) ? $this->header[$key] : '';
    }

    /**
     * 发送请求头
     */
    public function sendHeader()
    {
        $status = isset($this->status_tip[$this->status]) ? $this->status_tip[$this->status] : '';
        header('HTTP/1.1 ' . $status);
        foreach ($this->headers as $key => $val) {
            header($key . ':' . $val);
        }
        $this->headers = [];
    }

    /**
     * 发送header加发送一段内容
     * @param mixed $str 发送一段内容,如果内容是数组则会调用json发送
     */
    public function send($str = null)
    {
        $this->sendHeader();
        if ($str === null) {
            return $this;
        }
        if (is_array($str)) {
            $this->json($str);
        } else {
            echo $str;
        }
        return $this;
    }

    /**
     * 设置状态码
     * @param  mixed   $status 状态码
     * @return array
     */
    public function setStatus($status = null)
    {
        $this->status = (int) $status;
        return $this;
    }

    /**
     * 获取状态吗
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 下载文件，输出附件
     * @param string  $file   文件地址或者一段文字数据，当为文字数据时 isdata必须设置为true
     * @param string  $name   下载的文件名
     * @param boolean $isdata 下载的是文件还是一段字符数据 默认是false 为文件
     */
    public function file($file, $name = '', $isdata = false)
    {
        if (!$file || (!file_exists($file) && !$isdata)) {
            return false;
        }
        if (!$isdata) {
            $filesize = filesize($file);
            if ($name == '') {
                $pos  = strrpos($file, DIRECTORY_SEPARATOR) ? strrpos($file, DIRECTORY_SEPARATOR) : strrpos($file, '/');
                $name = substr($file, $pos + 1);
            }
            if (!$name) {
                $name = 'download';
            }
        } else {
            $filesize = strlen($file);
        }

        $this->setHeader('Content-type', 'application/octet-stream');
        $this->setHeader('Accept-Range', 'byte');
        $this->setHeader('Accept-Length', $filesize);
        $this->setHeader('Content-Disposition', "attachment; filename=" . $name);
        $this->sendHeader();
        if ($isdata) {
            exit($file);
        } else {
            readfile($file);
        }
    }

    /**
     * 设置cookie
     * @param string $key  cookie键名
     * @param string $name cookie值
     */
    public function setCookie($key, $name)
    {
        $cookie = new \Vitex\Middleware\Cookie();
        $cookie->setCookie($key, $name);
        return $this;
    }

    /**
     * 清空所有cookie或者指定的cookie
     * @param  string $key    cookie名
     * @return object $this
     */
    public function clearCookie($key)
    {
        $cookie = new \Vitex\Middleware\Cookie();
        $cookie->clearCookie($key);
        return $this;
    }

    /**
     * 跳转
     * @param string  $url    跳转的地址
     * @param integer $status 跳转状态码
     */
    public function redirect($url, $status = 302)
    {
        if ($status != 302) {
            $this->setStatus($status);
        }
        $this->setHeader('Location', $url);
        $this->sendHeader();
    }

    /**
     * 直接输出模板信息
     * @param string $tpl    模板地址
     * @param array  $data   传递给模板的数据
     * @param int    $status 状态
     */
    public function render($tpl, array $data = [], $status = null)
    {
        $vitex = \Vitex\Vitex::getInstance();
        $vitex->render($tpl, $data, $status);
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
            $this->methods[$pro] = Closure::bind($data, $this, 'Response');
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
            throw new \Exception('Not Method ' . $method . ' Found In Response!');
        }
        array_unshift($args, $this);
        return call_user_func_array($this->methods[$method], $args);
    }
}
