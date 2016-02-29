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

namespace vitex\middleware;

use vitex\Middleware;

/**
 * 请求方法重写的类，可以使用此方法通过代理重写，或者直接客户端通过参数重写方法
 */
class MethodOverride extends Middleware
{
    protected $overrideKey;
    public function __construct()
    {
    }

    public function call()
    {
        $env               = $this->vitex->env;
        $this->overrideKey = $this->vitex->getConfig('methodoverride.key');

        if ($env->get('HTTP_X_HTTP_METHOD_OVERRIDE')) {
            $env['ORIGINAL_METHOD'] = $env['REQUEST_METHOD'];
            $env['REQUEST_METHOD']  = $env['HTTP_X_HTTP_METHOD_OVERRIDE'];
        } elseif ($env['REQUEST_METHOD'] == "POST") {
            //form __METHOD
            $__method = $this->vitex->req->body[$this->overrideKey];
            if ($__method) {
                $env['ORIGINAL_METHOD'] = 'POST';
                $env['REQUEST_METHOD']  = $__method;
            }
        }
        $this->runNext();
    }
}
