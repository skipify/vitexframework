<?php declare(strict_types=1);
/**
 * Vitex 一个基于php7.0开发的 快速开发restful API的微型框架
 * @version  0.3.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex;

use Psr\Log\LoggerInterface;
use vitex\core\Env;
use vitex\core\event\EventEmitter;
use vitex\core\Exception;
use vitex\core\Loader;

use vitex\core\Request;
use vitex\core\Response;
use vitex\core\Route;
use vitex\core\RouteHandlerInterface;
use vitex\helper\Utils;
use vitex\service\ConfigProvider;
use vitex\service\Container;


if (!Utils::phpVersion('7.0')) {
    throw new Exception("I am at least PHP version 7.0.0");
}

class Vitex extends EventEmitter
{
    const VERSION = "1.0.0-alpha1";
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
     * 配置文件服务
     * @var array
     */
    private $configProvider;

    /**
     * 容器对象
     * @var Container
     */
    public $container;

    /**
     * 已经初始化或者注入的应用
     * @var array
     */
    private $initApps = [];

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

    /**
     * @var core\Route
     */
    public $route;

    /**
     * Vitex constructor.
     * @param mixed $setting
     */
    private function __construct()
    {
        $this->execTime();//记录执行开始时间
        //注册加载 加载器
        require __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . "Loader.php";
        $this->loader = new Loader();
        $this->loader->addNamespace('\vitex', __DIR__);
        $this->loader->register();

        $this->container = new Container();


        //初始化各种变量
        $this->env = $this->container->get(Env::class);
        $this->route = $this->container->get(Route::class);
        //初始化 request response
        $this->req = $this->container->get(Request::class);
        $this->res = $this->container->get(Response::class);

        $this->configProvider = $this->container->get(ConfigProvider::class);

        //view视图
        $this->view = null;
        //日志
        $this->log = $this->container->get(LoggerInterface::class);

        $this->using(new middleware\Csrf());
        //添加第一个中间件，他总是最后一个执行
        $this->using(new middleware\MethodOverride());
        //命令行路由
        $this->using(new middleware\Cli());
        date_default_timezone_set('Asia/Shanghai');


    }

    /**
     * 捕获处理异常
     *
     * @param  int $errno 错误代码
     * @param  string $errstr 错误提示
     * @param  string $errfile 错误文件
     * @param  int|string $errline 错误行
     * @return bool
     */
    public function errorHandler($errno, $errstr = '', $errfile = '', $errline = '')
    {
        if (!($errno & error_reporting())) {
            return null;
        }
        $this->log->error("Code:{code}\tMsg:{msg}\tFile:{file}\tLine:{line}",
            ['code' => $errno, 'msg' => $errstr, 'file' => $errfile, 'line' => $errline]);
    }

    /**
     * 获取框架实例
     * @param mixed $setting
     * @return null|Vitex
     */

    public static function getInstance($setting = [])
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($setting);
        }
        return self::$_instance;
    }

    /**
     * 设置已经初始化或者已经注入的应用
     * @param $app
     * @param $dir
     * @return $this
     */
    public function setInitApps($app, $dir)
    {
        $this->initApps[$app] = $dir;
        return $this;
    }

    /**
     * 获取已经初始化或者注入的应用
     * @param string $app 获取的应用名称
     * @return array
     */
    public function getInitApps($app)
    {
        if ($app) {
            return $this->initApps[$app] ?? null;
        }
        return $this->initApps;
    }

    /**
     * 初始化一个应用,包括设置各种路径添加加载命名空间等
     * @param  string $app 应用的名称
     * @param  string $dir 应用的路径
     * @param  array|string $setting 批量设置配置
     * @param  array $middleware
     * @return $this
     */

    public function init($app, $dir, array $setting = [], array $middleware = [])
    {
        $_setting = [
            'templates.path' => $dir . '/' . $app . '/templates',
            'router.grouppath' => $dir . '/' . $app . '/route',
        ];
        $this->appName = $app;
        $setting = array_merge($_setting, $setting);
        $this->setConfig($setting);
        $namespace = $app;
        $this->loader->addNamespace('\\' . $namespace, $dir . '/' . $app . '/');
        //初始化预加载的中间件
        foreach ($middleware as $mw) {
            if ($mw instanceof Middleware) {
                $this->using($mw);
            }
        }
        $this->setInitApps($app, $dir);
        return $this;
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
        $defapps = $this->multiApps['default'] ?? [];
        if (!$apps && !$defapps) {
            throw new Exception("无法找到设置的初始化映射规则");
        }
        $app = null;
        $dir = null;
        $setting = [];
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
                $this->setInitApps($appname, $_dir);
            }
        }
        return $app;
    }

    /**
     * 注入一个应用到当前的应用中,使之在当前应用可以直接通过命名空间访问注入的应用
     * 注入的应用也可以注册到当前路由,但是需要指定完全限定名的命名空间
     * @param $app  string 应用名(目录名)
     * @param $path string 路径  ($path.$app可以访问)
     * @return $this
     * @throws Exception
     */
    public function injectApp($app, $path = '')
    {
        if (!defined('WEBROOT')) {
            throw new Exception('入口文件必须定义一个WEBROOT的变量到根目录');
        }
        $path = $path ? rtrim($path) . '/' : dirname(WEBROOT) . '/';
        $this->loader->addNamespace('\\' . $app, $path . $app);
        $this->setInitApps($app, $path);
        return $this;
    }

    /**
     * 获取分组配置信息
     * @param  array $apps 配置数组
     * @return array 配置
     */
    private function getAppConfig($apps)
    {
        $pathinfo = trim($this->env->getPathinfo(), '/');
        $pathinfos = explode('/', $pathinfo);
        $group = $pathinfos[0] ?? '';
        $_apps = null;
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
     * @param  array $map 一个映射的方式
     * @param  string $domain 一个域名，表示当前的所有操作都是在当前域名下得绑定，如果不指定则会适用于所有域名
     * @throws core\Exception
     * @return self
     */
    public function setAppMap(array $map, $domain = "default")
    {
        $domain = str_replace(['http://', '/'], '', $domain);
        $_map = [];
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
        $oldMap = $this->multiApps[$domain] ?? [];
        $this->multiApps[$domain] = array_merge($oldMap, $_map);
        return $this;
    }

    /**
     * 设置配置文件
     * @param  $name
     * @param  null $val
     * @throws Exception
     * @return self
     */
    public function setConfig($name, $val = null)
    {
        $this->configProvider->setConfig($name,$val);
        return $this;
    }

    /**
     * 获取配置
     * @param  string $name 配置名
     * @return mixed
     */
    public function getConfig($name)
    {
        return $this->configProvider->getConfig($name);
    }

    /**
     * 构造URL
     * @param  string $url url或者一个路由段
     * @param  array $params 关联数组转为querystring
     * @return string 最终的url
     */
    public function url($url, $params = [])
    {
        return $this->route->router->url($url, $params);
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
     * @param string $tpl 模板地址
     * @param array $data 传递给模板的数据
     * @param int $status 状态码，默认会输出200
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
     * @param  Middleware $call
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
     * @param  string /array/callable $pattern 匹配的url规则,多个匹配规则时可以传递一个数组或者中间件实例
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
     * 判断是否已经加载一个中间件
     * @param $class string 中间件类名
     * @return bool
     */
    public function isLoadMiddleware($class)
    {
        return in_array($class, $this->preMiddlewareArr) ? true : false;
    }

    /**
     * 获得已经加载的中间件列表
     * @return array
     */
    public function getLoadMiddleware()
    {
        return $this->preMiddlewareArr;
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
     * @param array $args 请求的参数，当参数超过2个的时候，中间的参数为中间件，该中间件仅在此次运行中执行
     */
    public function setRoute($method, $args)
    {
        $pattern = array_shift($args);
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
     * @param  mixed $class 分组对应的类的名字
     * @param  string $appName 多个应用时可以指定应用名字用于加载指定应用下的路由文件
     * @return self
     */
    public function group($pattern, $class, $appName = '')
    {
        if (is_string($class)) {
            $this->route->group($pattern, $class, $appName);
        } elseif (is_callable($class)) {
            //另外的绑定分组
            $func = \Closure::bind($class, $this, '\vitex\Vitex');
            $this->route->group($pattern, $func, $appName);
        }
        return $this;
    }

    /**
     * 注册中间件，所有的中间件都是通过invoke调用
     * 中间件方法其实是一个特殊的请求
     * @param  string /array $pattern 匹配的url规则,多个匹配规则时可以传递一个数组
     * @param  callable $call 执行的方法
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
        $args = func_get_args();
        $methods = array_shift($args);
        $methods = is_array($methods) ? $methods : explode(',', $methods);

        foreach ($methods as $method) {
            $method = strtoupper($method);
            $this->setRoute($method, $args);
        }
        return $this;
    }

    /**
     * 设置路由别名
     * @param $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->route->router->setAlias($alias);
        return $this;
    }

    /**
     * 根据别名获取路由信息
     * @param $alias
     * @param array $data
     * @return mixed|null
     */
    public function getByAlias($alias, array $data = [])
    {
        return $this->route->router->getByAlias($alias, $data);
    }

    /**
     * 路有执行前单独执行此方法
     * @param callable $callable
     * @return $this
     */
    public function before(callable $callable)
    {
        $this->route->router->before($callable);
        return $this;
    }

    /**
     * 路由执行后调用的方法
     * @param callable $callable
     * @return $this
     */
    public function after(callable $callable)
    {
        $this->route->router->after($callable);
        return $this;
    }

    /**
     * 在路由外面单独包裹一个方法执行路由包裹方法
     * 此类发方法会把路由当做一个方法传入到wrap方法中
     * @param callable $wrapper
     * @return $this
     */
    public function wrap(callable $wrapper)
    {
        $this->route->router->wrap($wrapper);
        return $this;
    }

    public function handler(RouteHandlerInterface $handler)
    {
        $this->route->router->handler($handler);
        return $this;
    }

    /**
     * 页面执行时间
     *
     * @author skipify
     * @param string $symbol 记录时间的标示
     * @return int
     */
    public function execTime($symbol = '__start')
    {
        static $_time_stamp = [];
        $now = microtime(true) * 1000;//当前毫秒
        if (isset($_time_stamp[$symbol])) {
            return $now - $_time_stamp[$symbol];
        } else {
            $_time_stamp[$symbol] = $now;
        }
        return $now;
    }

    /**
     * 执行已经加载的中间件
     * @return $this
     */
    public function runLoadMiddleware()
    {
        //预处理中间件
        $this->using(new middleware\Cookie());
        //预处理中间件执行
        if ($this->preMiddleware) {
            $this->preMiddleware->call();
        }
        return $this;
    }

    /**
     * 路由分发
     * 加载路由以及分组，匹配执行
     * @return $this
     */
    public function routeDispatch()
    {
        $this->emit('sys.before.router');
        //分组
        $this->route->applyGroup();
        $this->route->next();
        $this->emit('sys.after.router');
        return $this;
    }

    /**
     * 启动程序
     */
    public function run()
    {
        //输出指定编码以及格式
        $this->res->setHeader("Content-Type", "text/html;charset=" . $this->getConfig("charset"))->sendHeader();
        set_error_handler(array($this, 'errorHandler'));
        $this->runLoadMiddleware();
        $this->routeDispatch();

        restore_error_handler();
    }


    public function __get($name)
    {

    }

}
