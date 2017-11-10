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
 * 会话管理基类
 */

namespace vitex\ext\sessionhandler;


use vitex\Vitex;

class SessionHandler
{
    protected $vitex;
    public function __construct()
    {
        $this->vitex = Vitex::getInstance();
    }
}