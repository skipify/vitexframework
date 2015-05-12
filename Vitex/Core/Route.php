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
namespace Vitex\Core;

/**
 * 系统路由的方法，用于根据URL来定位到要访问的方法
 */
class Route
{

    private $themethod = "GET";
    public $router;
    protected $_router; //路由callable
    private $_notfound;
    protected $_routerGroup = [];
    protected $_groupPath   = '';
    protected $groupurl     = '';
    protected $env;

    public function __construct()
    {
        $this->env       = Env::getInstance();
        $this->themethod = $this->env->method();
        $this->router    = new Router;
        $this->_notfound = function () {
            echo '<h1>404 Not Found</h1>';
            $vitex = \Vitex\Vitex::getInstance();
            if ($vitex->getConfig('debug')) {
                //输出调试信息
                $url      = $this->env->getPathinfo();
                $patterns = $this->router->getPattern();
                echo '<h2>Router Detail</h2>';
                echo '<strong>URL</strong>: ' . $url . '<br /><strong>Matcher:</strong>';
                echo '<ul>';
                foreach ($patterns as list($method, $matcher, $call, $pattern)) {
                    echo sprintf('<li><span>Method: %s </span> <span>Url: %s</span> <span style="width:500px">RegExp: %s</span></li>', $method, $pattern, $matcher);
                }
                echo '</ul>';
                echo '<style type="text/css">li span{display:inline-block;width:150px;}</style>';
            }
        };
    }

    /**
     * 路由分组
     * @param string $pattern 分组标识
     * @param string $class   分组文件名或者一个包含注册路由的callable
     */
    public function group($pattern, $class)
    {
        $pattern                      = trim($pattern, '/');
        $this->_routerGroup[$pattern] = $class;
        return $this;
    }

    /**
     * 设置 分组的默认路径
     * @param  string     $path                            路径
     * @return obj/string 路由对象或者分组路径
     */
    public function setGroupPath($path)
    {
        $this->_groupPath = rtrim($path, '/' . DIRECTORY_SEPARATOR) . '/';
        return $this;
    }

    /**
     * 获取分组路径
     * @return string
     */
    public function getGroupPath()
    {
        if (!$this->_groupPath) {
            $path = \Vitex\Vitex::getInstance()->getConfig('router.group_path');
            $this->setGroupPath($path);
        }
        return $this->_groupPath;
    }

    /**
     * 应用分组信息
     */
    public function applyGroup()
    {
        $url = $this->env->getPathinfo();
        $url = trim($url, '/');
        if (!$this->_routerGroup) {
            return false;
        }
        $this->vitex = \Vitex\Vitex::getInstance();
        foreach ($this->_routerGroup as $p => $g) {
            $len = strlen($p);
            if (substr($url, 0, $len) != $p) {
                continue;
            }
            $this->groupurl = $p;
            $this->parseGroupMethod($g);
            break;
        }
        return $this;
    }

    /**
     * 解析分组的内容
     * @param mixed $g 可执行的方法或者一个文件
     */
    private function parseGroupMethod($g)
    {
        //匹配到一个分组
        if (is_callable($g)) {
            call_user_func($g);
        } else {
            //兼容扩展名
            if (substr($g, -4) != '.php') {
                $g .= '.php';
            }
            $vitex = $this->vitex;
            //绝对路径
            $isload = false;
            if (strpos($g, '/') !== false && file_exists($g)) {
                require $g;
                $isload = true;
            } else {
                $g = $this->getGroupPath() . $g;
                if (file_exists($g)) {
                    require $g;
                    $isload = true;
                }
            }
            //加载分组信息出错
            if (!$isload) {
                throw new \Exception('加载分组文件 ' . $g . ' 出错，无法找到文件');
            }
        }
    }

    /**
     * 注册路由信息
     * @param string $method   路由匹配方法
     * @param string $pattern  路由匹配
     * @param mixed  $callable 执行的方法
     */
    public function register($method, $pattern, $callable)
    {
        $pattern = $this->groupurl . $pattern;
        $this->router->map($method, $pattern, $callable);
        return $this;
    }

    /**
     * 404页面
     * @param callable $call 404执行的方法
     */
    public function notFound(callable $call = null)
    {
        if ($call === null) {
            $vitex = \Vitex\Vitex::getInstance();
            call_user_func($this->_notfound, $vitex->req, $vitex->res, $next);
        } else {
            $this->_notfound = $call;
        }
        return $this;
    }

    /**
     * 执行下一个匹配的URL规则
     * @return function [description]
     */
    public function next()
    {
        if (!$this->_router) {
            $this->_router = $this->router->getRouter();
        }

        $vitex = \Vitex\Vitex::getInstance();
        $call  = $this->_router->current();
        $self  = $this;
        if (is_callable($call)) {
            call_user_func($call, $vitex->req, $vitex->res, function () {$this->nextRouter();});
        } else {
            call_user_func($this->_notfound, $vitex->req, $vitex->res, function () {$this->nextRouter();});
        }
        return $this;
    }

    /**
     * 执行下一次路由匹配
     * @return void
     */
    private function nextRouter()
    {
        $this->_router->next();
        $this->next();
    }
}