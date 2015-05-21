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
    public function get($key)
    {
        $key = strtoupper($key);
        return $this->env->get($key);
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
}
