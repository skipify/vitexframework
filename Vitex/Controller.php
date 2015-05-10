<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.3.0
 *
 * @package Vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
namespace Vitex;

class Controller
{
    public $vitex;
    public $req;
    public $res;
    //处理一些变量
    public function __construct()
    {
        $this->vitex = Vitex::getInstance();
        $this->req   = $this->vitex->req;
        $this->res   = $this->vitex->res;
    }

    /**
     * 找不到定义的方法会自动去Vitex对象中查找
     * @param  string $method                    方法名
     * @param  array  $args                      参数
     * @return mixed  取决于调用的方法
     */
    public function __call($method, $args)
    {
        if (method_exists($this->vitex, $method)) {
            return call_user_func_array(array($this->vitex, $method), $args);
        } else {
            throw new \Exception('No Method ' . $method . ' Found!!');
        }
    }

    /**
     * 当设置不存在的属性时会把属性设置到Vitex对象中
     * @param string $name 属性名称
     * @param mixed  $val  属性值
     */
    public function __set($name, $val)
    {
        if ($name) {
            $this->vitex->{$name} = $val;
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
        return $this->vitex->{$name};
    }
}
