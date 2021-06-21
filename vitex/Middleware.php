<?php
/**
 * Vitex 一个基于php8.0开发的 快速开发restful API的微型框架
 * @version  2.0.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex;

/**
 * 中间件方法的基类
 */
abstract class Middleware
{
    /**
     * @var Vitex
     */
    protected $vitex; //应用
    /**
     * @var Middleware
     */
    protected $nextMw; //下一个预处理中间件

    public function __construct()
    {
    }

    /**
     * 设置下一个要执行的预处理中间件
     * @param Middleware $call 中间件
     * @return void
     */
    final public function nextMiddleware(Middleware $call)
    {
        $this->nextMw = $call;
    }

    /**
     * 设置当前应用
     * @param Vitex $vitex Vitex类的一个实例
     * @return self
     */
    public function setVitex($vitex)
    {
        $this->vitex = $vitex;
        return $this;
    }

    /**
     * 是否是进程级别的中间件，默认都是请求级别的
     * 请求级别的中间件在每次请求时候都会初始化
     * 进程级别的只会初始化一次，适合在cli模式下运行，例如 swoole环境，征程的cgi/fpm环境2者没区别
     * @return false
     */
    public function isThreadLevel()
    {
        return false;
    }

    /**
     * 执行下一个预处理中间件调用
     * @return void
     */
    final public function runNext()
    {
        if ($this->nextMw) {
            $this->nextMw->call();
        }
    }

    /**
     * 执行中间件，使之生效
     * @return [type] [description]
     */
    abstract public function call();
}
