<?php
/**
 * 命令行执行时支持路由
 *  php index.php /home/user
 *  php index.php /user/edit?id=1
 *  php index.php /user/edit id=1
 */

namespace vitex\middleware;


use vitex\Middleware;

class Cli extends Middleware
{

    /**
     * 是否为CLI模式运行
     * @return bool
     */
    public function isCli()
    {
        if (PHP_SAPI == 'cli') {
            return true;
        }
        return false;
    }

    /**
     * 根据条件重设pathinfo
     */
    public function setPathInfo()
    {
        $argv = $_SERVER['argv'];
        $pathinfo = empty($argv[1]) ? '/' : $argv[1];
        if (strpos($pathinfo, '?') === false) {
            $queryString = empty($argv[2]) ? '' : $argv[2];
        } else {
            list($pathinfo, $queryString) = explode('?', $pathinfo);
        }
        $this->vitex->env->setPathinfo($pathinfo);
        //设置查询字符串
        if ($queryString) {
            parse_str($queryString, $get);
            $this->vitex->req->query->import($get);
        }
    }

    public function call()
    {
        if ($this->isCli()) {
            $this->setPathInfo();
        }
        $this->runNext();
    }

}