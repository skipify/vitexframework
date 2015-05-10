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

namespace Vitex;

/**
 * 中间件方法的基类
 */
abstract class Middleware
{

    protected $vitex; //应用
    protected $nextMw; //下一个预处理中间件
    public function __construct()
    {
    }

    /**
     * 设置下一个要执行的预处理中间件
     * @param  \Vitex\Middleware $call 中间件
     * @return void
     */
    final public function nextMiddleware(\Vitex\Middleware $call)
    {
        $this->nextMw = $call;
    }

    /**
     * 设置当前应用
     * @param object $vitex Vitex类的一个实例
     */
    public function setVitex($vitex)
    {
        $this->vitex = $vitex;
        return $this;
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
