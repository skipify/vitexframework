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
namespace vitex\core;

use vitex\Vitex;

/**
 * 系统路由的方法，用于根据URL来定位到要访问的方法
 */
class Route
{

    private $themethod = "GET";
    /**
     * @var Router
     */
    public $router;
    /**
     * @var \Generator
     */
    protected $_router; //路由callable
    private   $_notfound;
    protected $_routerGroup = [];
    protected $_groupPath   = '';
    protected $groupurl     = '';
    /**
     * @var Vitex
     */
    private $vitex;
    /**
     * @var Env
     */
    protected $env;

    public function __construct()
    {
        $this->env = Env::getInstance();
        $this->themethod = $this->env->method();
        $this->router = new Router;
        $this->_notfound = function () {
            echo '<h1>404 Not Found</h1>';
            $vitex = Vitex::getInstance();
            if ($vitex->getConfig('debug')) {
                //输出调试信息
                $url = $this->env->getPathinfo();
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
     * @param  string $pattern 分组标识
     * @param  string $class 分组文件名或者一个包含注册路由的callable
     * @param string $appName  指定分组的路由文件
     * @return self
     */
    public function group($pattern, $class, $appName = '')
    {
        if ($pattern !== '/') {
            //兼容 /的分组路由
            $pattern = trim($pattern, '/');
        }
        $this->_routerGroup[$pattern] = [$class, $appName];
        return $this;
    }

    /**
     * 设置 分组的默认路径
     * @param  string $path 路径
     * @return self
     */
    public function setGroupPath($path)
    {
        $this->_groupPath = rtrim($path, '/' . DIRECTORY_SEPARATOR) . '/';
        return $this;
    }

    /**
     * 获取分组路径
     * @return string 分组路径
     */
    public function getGroupPath()
    {
        if (!$this->_groupPath) {
            $path = Vitex::getInstance()->getConfig('router.grouppath');
            $this->setGroupPath($path);
        }
        return $this->_groupPath;
    }

    /**
     * 应用分组信息
     * @return self
     */
    public function applyGroup()
    {
        $url = $this->env->getPathinfo();
        $url = trim($url, '/');
        if (!$this->_routerGroup) {
            return false;
        }
        //提取第一段为分组信息
        $urls = explode('/', $url);
        $findGroup = false;
        $this->vitex = Vitex::getInstance();
        foreach ($this->_routerGroup as $p => list($g, $appName)) {
            //动态计算分组，支付多级分组
            $groupCount = substr_count($p, '/');
            $groupCount = $groupCount + 1;
            $gstr = implode('/', array_slice($urls, 0, $groupCount));
            if ($p != $gstr) {
                //当绑定分组为 / 时此处有bug
                continue;
            }
            $this->groupurl = $p;
            $this->parseGroupMethod($g, $appName);
            $findGroup = true;
            break;
        }

        //没有找到分组信息,查询是否有 / 的分组
        if (false === $findGroup && isset($this->_routerGroup['/'])) {
            $this->groupurl = '/';
            list($g, $appName) = $this->_routerGroup['/'];
            $this->parseGroupMethod($g, $appName);
        }
        return $this;
    }

    /**
     * 解析分组的内容
     * @param  string $g 可执行的方法或者一个文件
     * @param string $appName 指定的应用名称
     * @throws Exception
     */
    private function parseGroupMethod($g, $appName)
    {
        //匹配到一个分组
        if (is_callable($g)) {
            call_user_func($g);
        } else {
            //兼容扩展名
            if (substr($g, -4) != '.php') {
                $g .= '.php';
            }
            //绝对路径
            $isload = false;
            $vitex = Vitex::getInstance();
            /**
             * 此变量用于设定的路由文件中使用
             */
            if (strpos($g, '/') !== false && file_exists($g)) {
                require $g;
                $isload = true;
            } else {
                //兼容appName,重设路由分组文件路径
                if ($appName && ($dir = $vitex->getInitApps($appName))) {
                    $this->setGroupPath($dir . '/' . $appName . '/router');
                }
                //设置路由应用
                $this->router->setRouteApp(($appName ?: $vitex->appName));

                $g = $this->getGroupPath() . $g;
                if (file_exists($g)) {
                    require $g;
                    $isload = true;
                }
            }
            //加载分组信息出错
            if (!$isload) {
                throw new Exception('加载分组文件 ' . $g . ' 出错，无法找到文件');
            }
        }
    }

    /**
     * 注册路由信息
     * @param  string $method 路由匹配方法
     * @param  string $pattern 路由匹配
     * @param  mixed $callable 执行的方法
     * @return self
     */
    public function register($method, $pattern, $callable)
    {
        if ($pattern[0] != '|') {
            //非正则表达式的匹配段
            $pattern = $this->groupurl . $pattern;
        }
        $this->router->map($method, $pattern, $callable);
        return $this;
    }

    /**
     * 404页面
     * @param  callable $call 404执行的方法
     * @return self
     */
    public function notFound(callable $call = null)
    {
        if ($call === null) {
            $vitex = Vitex::getInstance();
            call_user_func($this->_notfound, $vitex->req, $vitex->res, function () {
                $this->next();
            });
        } else {
            $this->_notfound = $call;
        }
        return $this;
    }

    /**
     * 执行下一个匹配的URL规则
     * @return self
     */
    public function next()
    {
        if (!$this->_router) {
            $this->_router = $this->router->getRouter();
        }

        $vitex = Vitex::getInstance();
        $call = $this->_router->current();
        if (is_callable($call)) {
            call_user_func($call, $vitex->req, $vitex->res, function () {
                $this->nextRouter();
            });
        } else {
            call_user_func($this->_notfound, $vitex->req, $vitex->res, function () {
                $this->nextRouter();
            });
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
