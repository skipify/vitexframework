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

namespace vitex;

use vitex\core\Exception;
use vitex\core\Loader;
use vitex\helper\LogWriter;
use vitex\middleware;
use vitex\View;

if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    throw new Exception("I am at least PHP version 5.5.0");
}

class Vitex
{
    const VERSION = "0.8.3";
    /**
     * App instance
     */
    private static $_instance = null;
    /**
     * APP名
     * @var string
     */
    public $appName = 'app';
    /**
     * This is a data container;
     * @var array
     */
    private $settings;
    /**
     * 默认的系统配置
     * @var array
     */
    private $defaultSetting = array(
        'debug'                 => true,
        // View
        'templates.path'        => './templates',
        'templates.ext'         => '.html',
        'view'                  => '\vitex\View',
        'callback'              => 'callback', //jsonp时自动获取的值
        'router.group_path'     => '',
        'router.compatible'     => false, //路由兼容模式，不支持pathinfo的路由开启
        'router.case_sensitive' => false, //是否区分大小写
        'methodoverride.key'    => '__METHOD', //url request method 重写的key
        'cookies.encrypt'       => false, //是否启用cookie加密
        'cookies.lifetime'      => '20 minutes',
        'cookies.path'          => '/',
        'cookies.domain'        => null,
        'cookies.secure'        => false,
        'cookies.httponly'      => false,
        'cookies.secret_key'    => 'Vitex is a micro restfull framework',
        'charset'               => 'utf-8',
    );
    /**
     * 两个内置的hooks执行点
     * before.router
     * after.router
     */
    protected $hooks = [];
    /**
     * 保存debug的一些方便的信息
     */
    protected $debuginfo = [];
    /**
     * 预处理中间件
     * @var Middleware
     */
    protected $preMiddleware;
    /**
     * 预处理中间件记录器
     */
    protected $preMiddlewareArr = [];
    /**
     * 多应用映射管理
     * @var array
     */
    private $multiApps = ['default' => []];

    /**
     * @var core\Env 环境变量
     */
    public $env;
    /**
     * @var core\Request
     */
    public $req;
    /**
     * @var core\Response
     */
    public $res;

    private function __construct()
    {
        //注册加载 加载器
        require __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . "Loader.php";
        $this->loader = new Loader();
        $this->loader->addNamespace('\vitex', __DIR__);
        $this->loader->register();

        //init app
        $this->settings = $this->defaultSetting;
        //初始化各种变量
        $this->env   = core\Env::getInstance();
        $this->route = new core\Route();
        //初始化 request response
        $this->req      = core\Request::getInstance();
        $this->res      = core\Response::getInstance();
        $this->res->url = function ($url, $params = []) {
            return $this->url($url, $params);
        };
        //view视图
        $this->view = null;
        //日志
        $this->log = new Log();
        //添加第一个中间件，他总是最后一个执行
        $this->using(new middleware\MethodOverride());
        date_default_timezone_set('Asia/Shanghai');
    }

    /**
     * 捕获处理异常
     *
     * @param  int        $errno   错误代码
     * @param  string     $errstr  错误提示
     * @param  string     $errfile 错误文件
     * @param  int|string $errline 错误行
     * @return bool
     */
    public function handler($errno, $errstr = '', $errfile = '', $errline = '')
    {
        if (!($errno & error_reporting())) {
            return;
        }
        $this->log->error("Code:{code}\tMsg:{msg}\tFile:{file}\tLine:{line}",
            ['code' => $errno, 'msg' => $errstr, 'file' => $errfile, 'line' => $errline]);
    }

    /**
     *     get App Instance
     * @return self
     */

    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * 初始化一个应用,包括设置各种路径添加加载命名空间等
     * @param  string       $app          应用的名称
     * @param  string       $dir          应用的路径
     * @param  array|string $setting      批量设置配置
     * @param  array        $middleware
     * @return self
     */

    public function init($app, $dir, array $setting = [], array $middleware = [])
    {
        $_setting = [
            'templates.path'   => $dir . '/' . $app . '/templates',
            'router.grouppath' => $dir . '/' . $app . '/route',
        ];
        $this->appName = $app;
        $setting       = array_merge($_setting, $setting);
        $this->setConfig($setting);
        $namespace = $app;
        $this->loader->addNamespace('\\' . $namespace, $dir . '/' . $app . '/');
        //初始化预加载的中间件
        foreach ($middleware as $mw) {
            if ($mw instanceof Middleware) {
                $this->using($mw);
            }
        }
    }

    /**
     * 多级应用处理
     * @throws core\Exception
     * @return string           当前路由到得应用名称
     */
    public function multiInit()
    {
        //domain > url
        $host = $this->env->get('HTTP_HOST');
        $apps = [];
        if (isset($this->multiApps[$host])) {
            $apps = $this->multiApps[$host];
        }
        $defapps = isset($this->multiApps['default']) ? $this->multiApps['default'] : [];
        if (!$apps && !$defapps) {
            throw new Exception("无法找到设置的初始化映射规则");
        }
        $app        = null;
        $dir        = null;
        $setting    = [];
        $middleware = null;
        if ($apps) {
            $_apps = $this->getAppConfig($apps);
            if ($_apps) {
                list($app, $dir, $setting, $middleware) = $_apps;
            }
        } else {
            $_apps = $this->getAppConfig($defapps);
            if ($_apps) {
                list($app, $dir, $setting, $middleware) = $_apps;
            }
        }
        //开始处理新的路由
        if ($app === null) {
            if ($this->getConfig('debug')) {
                throw new Exception('无法找到请求的处理方法');
            } else {
                $this->route->notFound();
            }
        }
        $this->init($app, $dir, $setting, $middleware);
        //注册其他应用的自动加载
        foreach ($this->multiApps as $apps) {
            if (!$apps) {
                continue;
            }
            foreach ($apps as $_app) {
                list($appname, $_dir) = $_app;
                if ($app != $appname) {
                    $namespace = $appname;
                    $this->loader->addNamespace('\\' . $namespace, $_dir . '/' . $appname . '/');
                }
            }
        }
        return $app;
    }

    /**
     * 获取分组配置信息
     * @param  array $apps    配置数组
     * @return array 配置
     */
    private function getAppConfig($apps)
    {
        $pathinfo  = trim($this->env->getPathinfo(), '/');
        $pathinfos = explode('/', $pathinfo);
        $group     = isset($pathinfos[0]) ? $pathinfos[0] : '';
        $_apps     = null;
        if (isset($apps[$group])) {
            $_apps = $apps[$group];
            array_shift($pathinfos);
            $this->env->setPathinfo(implode('/', $pathinfos));
        } elseif (isset($apps['vitex.default'])) {
            $_apps = $apps['vitex.default'];
        }
        return $_apps;
    }

    /**
     * 多应用时的映射方式，当您一个大型的项目需要多个应用配合时需要使用此种方式更好的组织代码
     * 例如一个后台管理项目分为 前台以及管理员的后台，此时可以创建两个应用，单独负责自己的事宜
     * 域名的映射规则高于目录的级别
     * [
     *     'symbol' => [appname,dirname,setting,middleware] //后两个参数可以省略
     * ]
     * @param  array            $map    一个映射的方式
     * @param  string           $domain 一个域名，表示当前的所有操作都是在当前域名下得绑定，如果不指定则会适用于所有域名
     * @throws core\Exception
     * @return self
     */
    public function setAppMap(array $map, $domain = "default")
    {
        $domain = str_replace(['http://', '/'], '', $domain);
        $_map   = [];
        //过滤数据,格式化配置参数
        foreach ($map as $key => $val) {
            $paramLen = count($val);
            if ($paramLen < 2) {
                throw new Exception($key . '映射的应用配置参数不正确');
            }
            if ($paramLen == 2) {
                $val[] = [];
                $val[] = [];
            } elseif ($paramLen == 3) {
                $val[] = [];
            }
            if ($key === 0) {
                $skey = 'vitex.default';
            } else {
                $skey = trim($key, '/');
            }

            $_map[$skey] = $val;
        }
        $oldMap                   = isset($this->multiApps[$domain]) ? $this->multiApps[$domain] : [];
        $this->multiApps[$domain] = array_merge($oldMap, $_map);
        return $this;
    }

    /**
     * 设置配置文件
     * @param  $name
     * @param  null        $val
     * @throws Exception
     * @return self
     */
    public function setConfig($name, $val = null)
    {
        $setting = $this->settings;
        if (is_array($name)) {
            $setting = array_merge($setting, $name);
        } elseif ($val === null) {
            if (file_exists($name)) {
                $configs = include $name;
                $setting = array_merge($setting, $configs);
            } else {
                throw new Exception("不存在的配置文件:" . $name);
            }
        } else {
            $setting[$name] = $val;
        }
        $this->settings = $setting;
        return $this;
    }

    /**
     * 构造URL
     * @param  string $url           url或者一个路由段
     * @param  array  $params        关联数组转为querystring
     * @return string 最终的url
     */
    public function url($url, $params = [])
    {
        return $this->route->router->url($url, $params);
    }

    /**
     * 获取配置
     * @param  string  $name 配置名
     * @return mixed
     */
    public function getConfig($name)
    {
        $setting = $this->settings;
        return isset($setting[$name]) ? $setting[$name] : null;
    }

    /**
     * 注册钩子函数
     * @param  string   $name     钩子名称
     * @param  callable $call     可执行的方法
     * @param  integer  $priority 执行的优先级，数字越大越提前
     * @return self
     */
    public function hook($name, callable $call, $priority = 100)
    {
        $priority             = intval($priority);
        $this->hooks[$name][] = array($call, $priority);
        return $this;
    }

    /**
     * 执行钩子方法
     * @param string $name 钩子名称
     */
    public function applyHook($name)
    {
        $calls = $this->getHooks($name);
        usort($calls, function ($a, $b) {
            return ($b[1] - $a[1]);
        });
        $args = func_get_args();
        array_shift($args);
        foreach ($calls as list($call, $priority)) {
            call_user_func_array($call, $args);
        }
    }

    /**
     * 获取指定钩子或者所有的hooks
     * @param  string  $name 钩子的名字
     * @return array
     */
    public function getHooks($name = null)
    {
        if ($name == null) {
            return $this->hooks;
        }
        return isset($this->hooks[$name]) ? $this->hooks[$name] : array();
    }

    /**
     * 启用view视图
     * @return View
     */
    public function view()
    {
        if ($this->view !== null && ($this->view instanceof View)) {
            return $this->view;
        }
        $this->view = new View();
        return $this->view;
    }

    /**
     * 直接输出模板信息
     * @param string $tpl    模板地址
     * @param array  $data   传递给模板的数据
     * @param int    $status 状态码，默认会输出200
     */
    public function render($tpl, array $data = [], $status = null)
    {
        if ($this->view === null) {
            $this->view = $this->view();
        }
        if ($status !== null) {
            $this->res->setStatus($status)->send();
        }
        $this->view->display($tpl, $data);
    }

    /**
     * 预处理中间件
     * @param  Middleware       $call
     * @throws core\Exception
     * @return self
     */
    private function preUse(Middleware $call)
    {
        $class = get_class($call);
        if (in_array($class, $this->preMiddlewareArr)) {
            throw new Exception($class . ' Pre-Middleware has loaded');
        }
        $this->preMiddlewareArr[] = $class;
        if ($this->preMiddleware) {
            $call->nextMiddleware($this->preMiddleware);
        }
        $call->setVitex($this);
        $this->preMiddleware = $call;
        return $this;
    }

    /**
     * 注册中间件，所有的中间件都是通过using调用
     * @param  string   /array/callable $pattern 匹配的url规则,多个匹配规则时可以传递一个数组或者中间件实例
     * @param  callable /null           $call 执行的方法
     * @return self
     */
    public function using($pattern, $call = null)
    {
        if ($call == null && ($pattern instanceof Middleware)) {
            return $this->preUse($pattern);
        }
        return $this->invoke($pattern, $call);
    }

    /**
     * 直接执行中间件
     * @param  $middleware Middleware
     * @throws Exception
     * @return $this
     */
    public function runMiddleware($middleware)
    {
        if ($middleware instanceof Middleware) {
            $middleware->setVitex($this);
            $middleware->call();
            return $this;
        }
        throw new Exception("中间件参数不是有效的中间件");
    }

    /**
     * 注册路由请求
     * @param string $method 请求方法
     * @param array  $args   请求的参数，当参数超过2个的时候，中间的参数为中间件，该中间件仅在此次运行中执行
     */
    public function setRoute($method, $args)
    {
        $pattern  = array_shift($args);
        $callable = array_pop($args);

        //包含额外的参数
        if (count($args) > 0) {
            //额外的参数为单独的调用中间件
            foreach ($args as $call) {
                if (!is_callable($call)) {
                    continue;
                }
                $this->invoke($pattern, $call);
            }
        }
        $this->route->register($method, $pattern, $callable);
    }

    /**
     * 设置路由参数 匹配字段 类型限制的正则表达式
     * $this->setRouteRegexp('username','[a-z]{5,10}');
     * $this->get('/:user@username',function(){})
     * 可以匹配 /asdtc  但是不可以匹配 /asd
     *
     * @param  string /array $name 名称
     * @param  [mixed $val   正则值
     * @return self
     */
    public function setRouteRegexp($name, $val = null)
    {
        $this->route->router->setRegexp($name, $val);
        return $this;
    }

    /**
     * 路由分组
     * @param  string $pattern 分组标识 url的一部分
     * @param  string $class   分组对应的类的名字
     * @return self
     */
    public function group($pattern, $class)
    {
        $this->route->group($pattern, $class);
        return $this;
    }

    /**
     * 注册中间件，所有的中间件都是通过invoke调用
     * 中间件方法其实是一个特殊的请求
     * @param  string   /array $pattern 匹配的url规则,多个匹配规则时可以传递一个数组
     * @param  callable $call  执行的方法
     * @return self
     */
    private function invoke($pattern, callable $call)
    {
        $pattern = is_array($pattern) ? $pattern : [$pattern];
        foreach ($pattern as $val) {
            $this->setRoute('INVOKE', [$val, $call]);
        }
        return $this;
    }

    /**
     * 路由
     */
    /**
     * 当多于一个callable的参数时，最后一个callable当做处理请求的“请求处理器”
     * 其余的callable都会当做匹配当前URL时执行的 中间件，而且中间件的执行要早于“请求处理器”的执行
     * @return self
     */
    public function get()
    {
        $args = func_get_args();
        $this->setRoute('GET', $args);
        return $this;
    }

    /**
     * @return self
     */
    public function post()
    {
        $args = func_get_args();
        $this->setRoute('POST', $args);
        return $this;
    }

    /**
     * @return self
     */
    public function put()
    {
        $args = func_get_args();
        $this->setRoute('PUT', $args);
        return $this;
    }

    /**
     * @return self
     */
    public function delete()
    {
        $args = func_get_args();
        $this->setRoute('DELETE', $args);
        return $this;
    }

    /**
     * @return self
     */
    public function options()
    {
        $args = func_get_args();
        $this->setRoute('OPTIONS', $args);
        return $this;
    }

    /**
     * @return self
     */
    public function all()
    {
        $args = func_get_args();
        $this->setRoute('ALL', $args);
        return $this;
    }

    /**
     * 404页面，如果不指定$call则会触发执行默认或者已经设定(如果设定过)的notfound方法
     * @param  $call
     * @return self
     */
    public function notFound(callable $call = null)
    {
        $this->route->notFound($call);
        return $this;
    }

    /**
     * Map方法，第一个参数必须为一个标识方法的字符串或者数组
     * $vitex->map('get,post',function(){})
     * $vitex->map(['get','post'],function(){})
     * @return self
     */
    public function map()
    {
        $args    = func_get_args();
        $methods = array_shift($args);
        $methods = is_array($methods) ? $methods : explode(',', $methods);

        foreach ($methods as $method) {
            $method = strtoupper($method);
            $this->setRoute($method, $args);
        }
        return $this;
    }

    /**
     * 启动程序
     */
    public function run()
    {
        //输出指定编码以及格式
        $this->res->setHeader("Content-Type", "text/html;charset=" . $this->getConfig("charset"))->sendHeader();
        set_error_handler(array($this, 'handler'));
        if ($this->getConfig('debug')) {
            $this->log->setWriter(new LogWriter());
        }

        //预处理中间件
        $this->using(new middleware\Cookie());
        //预处理中间件执行
        if ($this->preMiddleware) {
            $this->preMiddleware->call();
        }
        $this->applyHook('before.router');
        //分组
        $this->route->applyGroup();

        $this->route->next();
        $this->applyHook('after.router');
        restore_error_handler();
    }

}
