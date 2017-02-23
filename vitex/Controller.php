<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.3.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
namespace vitex;

use vitex\core\Exception;

/**
 * Vitex开发基类
 * @package Vitex
 * @method hook(string $name, callable $call, int $priority = 100)
 * @method applyHook(string $name)
 * @method getHooks(string $name)
 * @method runMiddleware(Middleware $middleware)
 * @method execTime(string $symbol)
 * @method url(string $url,array $params = [])
 * @method notFound(callable $call = null)
 * */
class Controller
{
    /**
     * @var Vitex
     */
    public $vitex;
    /**
     * @var \vitex\core\Request
     */
    public $req;
    /**
     * @var \vitex\core\Response
     */
    public $res;
    /**
     * @var \vitex\View
     */
    public $view;

    //处理一些变量
    public function __construct()
    {
        $this->vitex = Vitex::getInstance();
        //init app
        $this->route = $this->vitex->route;
        //初始化各种变量
        $this->env = $this->vitex->env;
        //初始化 request response
        $this->req = $this->vitex->req;
        $this->res = $this->vitex->res;

        //日志
        $this->log = $this->vitex->log;
    }

    /**
     * 获取配置
     * @param  string  $name 配置名
     * @return mixed
     */
    public function getConfig($name)
    {
        return $this->vitex->getConfig($name);
    }
    /**
     * 设置配置文件
     * @param  string /array $name 键值/数组配置
     * @param  string /null  $val 值
     * @return self
     */
    public function setConfig($name, $val = null)
    {
        return $this->vitex->setConfig($name, $val);
    }

    /**
     * 启用view视图
     * @return View
     */
    public function view()
    {
        //view视图
        $this->view = $this->vitex->view();
        return $this->view;
    }
    /**
     * 直接输出模板信息
     * @param string $tpl    模板地址
     * @param array  $data   传递给模板的数据
     * @param int    $status 状态码，默认会输出200
     */
    public function render($tpl, array $data = [], $status = null)
    {
        $this->vitex->render($tpl, $data, $status);
    }

    public function __call($method, $args)
    {
        if (method_exists($this->vitex, $method)) {
            return call_user_func_array(array($this->vitex, $method), $args);
        } else {
            throw new Exception('No Method ' . $method . ' Found!!');
        }
    }

    /**
     * 当调用不存在的属性时会自动去Vitex查找
     * @param  string $name          键值属性名
     * @return mixed  属性的值
     */
    public function __get($name)
    {
        if (!$name) {
            return null;
        }
        if (isset($this->vitex->{$name})) {
            return $this->vitex->{$name};
        }
        return null;
    }
}
