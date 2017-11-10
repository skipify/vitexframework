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

/**
 * 单条路由句柄，可以实现对单个路由添加各种包括或者执行前后的预处理操作
 */

namespace vitex\core;


interface RouteHandlerInterface
{

    /**
     * 在路由到的方法执行之前执行的方法
     * @param Request $req
     * @param Response $res
     * @return mixed
     */
    public function before(Request $req, Response $res);

    /**
     * 在路由到的方法执行之后执行的方法
     * @param Request $req
     * @param Response $res
     * @param mixed $data
     * @return mixed
     */
    public function after(Request $req, Response $res,$data);

    /**
     * 可以重新定义路由到的方法执行行为的方法
     * @param callable $callable
     * @param Request $req
     * @param Response $res
     * @param mixed $data
     * @return mixed
     */
    public function wrap(callable $callable, Request $req, Response $res,$data);
}