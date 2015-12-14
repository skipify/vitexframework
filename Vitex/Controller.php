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

class Controller extends Vitex
{
    /**
     * @var Vitex
     */
    public $vitex;
    public $req;
    public $res;
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
        //view视图
        $this->view = $this->vitex->view;
        //日志
        $this->log = $this->vitex->log;
    }
}
