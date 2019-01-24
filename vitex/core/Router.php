<?php declare(strict_types=1);
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

use vitex\helper\Set;
use vitex\Vitex;

/**
 * 路由记录器类，用于记录各种路由中间件的对应关系，并且完成URl和方法的匹配
 */
class Router
{
    /**
     * 所有注册的路由的基本信息
     *
     * 请求方法  0
     * 路由匹配正则 1
     * 执行方法 2
     * 原始路由字符串 3
     * 路由应用 4
     * 路由别名 5
     * 方法 6
     *
     * @var array
     */
    private $_patterns = [];
    /**
     * 环境变量实例
     * @var Env
     */
    protected $env;
    /**
     * 框架主类
     * @var null
     */
    protected $vitex = null;
    /**
     * 路由是否区分大小写
     * @var bool
     */
    protected $caseSensitive = false;
    /**
     * 当存在占位符时可以使用此配置的内容根据正则限定占位符的内容格式
     * @var array
     */
    protected $regexps = [];
    /**
     * 生成路由时的路由前缀
     * @var null
     */
    protected $cacheBaseurl = null;

    /**
     * 本次路由调用的类
     * @var null
     */
    protected $routeClass = null;
    /**
     * 本次路由调用类的方法
     * @var null
     */
    protected $routeMethod = null;
    /**
     * 路由的分组APP名称
     * 多个应用时路由分组文件时设定的APP名称
     * @var null
     */
    private $routeGroupApp = null;

    public function __construct()
    {
        $this->env = Env::getInstance();
        $this->setRegexp([
            'alphadigit' => '[0-9a-zA-Z]+',
            'digit' => '[0-9]+',
            'alpha' => '[a-zA-Z]+',
            'float' => '[0-9]+\.{1}[0-9]+',
        ]);
    }

    /**
     * 设置预支的正则表达式
     * @param  mixed $name 名称/或者关联数组
     * @param  string $regexp 正则
     * @return self
     */
    public function setRegexp($name, $regexp = null)
    {
        if (is_array($name)) {
            $this->regexps = array_merge($this->regexps, $name);
        } else {
            $this->regexps[$name] = $regexp;
        }
        return $this;
    }

    /**
     * 获取指定的正则表达式值
     * @param  string $name 名字
     * @return mixed 值
     */
    public function getRegexp($name = null)
    {
        if ($name === null) {
            return $this->regexps;
        }
        return $this->regexps[$name] ?? '[^/]+';
    }

    /**
     * 根据指定的参数生成url地址
     * @param  string $url 路有段，如果是个有效的Url则会直接返回
     * @param  array $params 参数段，会被转为 querystring
     * @return string 返回的链接地址
     */
    public function url($url, $params = [])
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        $url = '/' . ltrim($url, '/');
        $baseUrl = "";
        if ($this->cacheBaseurl === null) {
            $vitex = Vitex::getInstance();
            $baseUrl = $vitex->getConfig('baseurl');
        }
        $qs = http_build_query($params);
        return rtrim($baseUrl, '/') . $url . ($params ? '?' . $qs : '');
    }
    /*
    这里pattern 的命名规则为   字母 下划线 数字

     */
    /**
     * 判断当前字符是否复合参数的命名规则
     * @param  String $letter 字符
     * @return boolean
     */
    public function isValid($letter)
    {
        $ord = ord($letter);
        if (($ord >= 65 && $ord <= 90) || ($ord >= 97 && $ord <= 122) || $ord == 95 || $letter == '@') {
            return true;
        }
        return false;
    }

    /**
     * 提取出匹配路径中的分组信息
     * @param  string $matcher 分组路径
     * @return array  匹配的分组
     */
    public function getSlice($matcher)
    {
        $len = strlen($matcher);
        $temp = ''; //临时字符串
        $start = null;
        $slices = [];
        $hasColon = false; //是否包含:

        for ($i = 0; $i < $len; $i++) {
            $letter = $matcher[$i];

            /*可选*/
            $optionSeg = $this->getOptionPattern($letter, $i);
            if ($optionSeg) {
                $slices[] = $optionSeg;
                $temp = '';
                $start = null;
                $hasColon = false;
                continue;
            }

            if ($letter == ':') {
                $start = $i;
                $hasColon = true;
                continue;
            }
            if ($start !== null && $this->isValid($letter)) {
                $temp .= $letter;
            }
            if (!$this->isValid($letter) && $start !== null) {
                if ($hasColon) {
                    $slices[] = $this->getSlicePattern($temp, $start, $i);
                    $hasColon = false;
                } else {
                    $slices[] = [$temp, "(?<" . $temp . ">[^/]+)", $start, $i];
                }
                $start = null;
                $temp = '';
            }
        }
        if ($temp) {
            $slices[] = $this->getSlicePattern($temp, $start, $i);
        }
        return $slices;
    }

    /**
     * 获取匹配分组的字符串
     * @param  $temp
     * @param  $start
     * @param  $i
     * @return array
     */
    private function getSlicePattern($temp, $start, $i)
    {
        $regexp = '[^/]+';
        $name = $temp;
        if (strpos($temp, '@') !== false) {
            list($name, $regexpKey) = explode('@', $temp);
            $regexp = $this->getRegexp($regexpKey);
        }
        return [$temp, "(?<" . $name . ">" . $regexp . ")", $start, $i];
    }

    /**
     * 解析可选匹配的字符串
     * @param $letter
     * @param $i int 当前字符位置
     * @return mixed
     */
    private function getOptionPattern($letter, $i)
    {
        static $isOption = false;//是否是可选参数
        static $optionTemp = '';
        static $start = null;

        $start = $start === null ? $i : $start;
        $isOption = $letter == '[' ? true : $isOption;

        //排除?*的匹配
        //仅仅匹配 [/  这种形式
        if ($optionTemp == '[' && $letter != '/') {
            $isOption = false;
            $start = null;
            $optionTemp = '';
            return false;
        }

        if ($isOption) {
            $optionTemp .= $letter;
        }

        if ($letter == ']') {
            //处理
            $name = trim($optionTemp, '[]/:');
            $slice = $this->getSlicePattern($name, $start, $i);
            $slice[0] = $optionTemp;
            $slice[1] = '(\/' . $slice[1] . ')?';
            //还原标识数据
            $isOption = false;
            $optionTemp = '';
            $start = null;
            return $slice;
        }
        return false;
    }

    /**
     * 注册映射一个请求参数
     * @param  string $method 请求方法
     * @param  string $pattern 匹配参数
     * @param  mixed $call 执行的方法
     * @return self
     */

    public function map($method, $pattern, $call)
    {
        if ($this->vitex === null) {
            $this->vitex = Vitex::getInstance();
            $this->caseSensitive = $this->vitex->getConfig('router.case_sensitive');
        }
        $method = strtoupper($method);

        $matcher = $this->getPatternRegexp($pattern);
        /**
         * 请求方法
         * 路由匹配正则
         * 执行方法
         * 原始路由字符串
         * 路由应用
         * 路由别名
         * 方法
         */
        $this->_patterns[] = [$method, $matcher, $call, $pattern, $this->getRouteApp(), '', []];
        return $this;
    }

    /**
     * 给路由设置别名
     * @param $alias
     * @return $this
     * @throws Exception
     */
    public function setAlias($alias)
    {
        if (!is_string($alias)) {
            throw new Exception(Exception::CODE_PARAM_VALUE_ERROR_MSG . ' Alias Must A String', Exception::CODE_PARAM_VALUE_ERROR);
        }
        $lastPattern = array_pop($this->_patterns);
        if ($lastPattern) {
            $lastPattern[5] = $alias;
            $this->_patterns[] = $lastPattern;
        }
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
        $pattern = null;
        foreach ($this->_patterns as $_pattern) {
            if ($_pattern[5] == $alias) {
                $pattern = $_pattern;
                break;
            }
        }
        if ($pattern == null) {
            return null;
        }

        /**
         * 没有指定数据则会直接返回原始路由信息
         */
        if (!$data) {
            return $pattern[3];
        }

        foreach ($data as $key => $val) {
            $data[':' . $key] = $val;
            unset($data[$key]);
        }
        $url = str_replace(array_keys($data), array_values($data), $pattern[3]);
        /**
         * 替换掉占位符格式限制字符串
         */
        $formatLimit = array_map(function ($item) {
            return '@' . $item;
        }, array_keys($this->regexps));
        $url = str_replace($formatLimit, '', $url);
        return $url;
    }

    /**
     * 在路由外面单独包裹一个方法执行路由包裹方法
     * 此类发方法会把路由当做一个方法传入到wrap方法中
     * @param callable $wrapper
     * @return $this
     */
    public function wrap(callable $wrapper)
    {
        return $this->_method('wrap', $wrapper);
    }

    /**
     * 路有执行前单独执行此方法
     * @param callable $before
     * @return Router
     */
    public function before(callable $before)
    {
        return $this->_method('before', $before);
    }

    /**
     * 路由执行后调用的方法
     * @param callable $after
     * @return Router
     */
    public function after(callable $after)
    {
        return $this->_method('after', $after);
    }

    /**
     * 通过实现RouteHandlerInterface 设置路由调用方法
     * @param RouteHandlerInterface $handler
     * @return $this
     */
    public function handler(RouteHandlerInterface $handler)
    {
        $this->_method('before', [$handler, 'before']);
        $this->_method('wrap', [$handler, 'wrap']);
        $this->_method('after', [$handler, 'after']);

        return $this;
    }

    /**
     * 设置路由的执行方法
     * @param $name
     * @param callable $callable
     * @return $this
     */
    private function _method($name, callable $callable)
    {
        $lastPattern = array_pop($this->_patterns);
        if ($lastPattern) {
            $methods = $lastPattern[6];//路由方法
            $methods[$name] = $callable;
            $lastPattern[6] = $methods;
            $this->_patterns[] = $lastPattern;
        }
        return $this;
    }

    /**
     * 检测一个url是否符合给定的匹配规则
     * @param $pattern  string 匹配规则
     * @param $url string 匹配规则
     * @return bool
     */
    public function checkUrlMatch($pattern, $url)
    {
        $url = trim($url, '/');
        if (!$url) {
            $url = '/';
        }
        $matcher = $this->getPatternRegexp($pattern);
        if (preg_match($matcher, $url, $matches)) {
            return true;
        }
        return false;
    }

    /**
     * 根据匹配分组获取需要匹配的正则表达式字符串
     * @param $pattern
     * @return $matcher string
     */
    public function getPatternRegexp($pattern)
    {
        $matcher = $pattern;
        $matcher = trim($matcher, '/');
        $cases = $this->caseSensitive ? '' : 'i';

        if (!$matcher) {
            $matcher = '|^/$|';
        } elseif ($matcher === '*') {
            $matcher = '|^.*$|' . $cases;
        } elseif ($matcher[0] == '|') {
            //正则表达式
            $matcher = $matcher . $cases;
        } else {
            //替换 *为匹配除了 /分组之外的所有内容
            /**
             * 防止路由中出现正则表达式特殊字符 例如 - : <> 这样的特殊字符
             */
            $matcher = preg_quote($matcher,'|');
            $matcher = str_replace(['\*','\:','\[','\]','\.'],['*',':','[',']','.'],$matcher);

            $matcher = str_replace(['*', '?'], ['([^\/]*)', '([^\/]?)'], $matcher);
            $slices = $this->getSlice($matcher);
            foreach ($slices as list($slice, $reg)) {
                if (strpos($slice, '[') !== false) {
                    $matcher = str_replace($slice, $reg, $matcher);
                } else {
                    $matcher = str_replace(':' . $slice, $reg, $matcher);
                }
            }
            $matcher = '|^' . $matcher . '$|' . $cases;
        }
        return $matcher;
    }

    /**
     * 获取匹配的路由结果
     * @return \Generator [description]
     */
    public function getRouter()
    {
        $method = strtoupper($this->env->method());
        $url = $this->env->getPathinfo();
        //默认首页
        $url = rtrim($url, '/');
        $url = $url ? $url : '/';
        return $this->match($method, $url);
    }

    /**
     * 设置当前路由的app
     * @param $appName
     * @return $this
     */
    public function setRouteApp($appName)
    {
        $this->routeGroupApp = $appName;
        return $this;
    }

    /**
     * 获取当前路由的appName
     * @return null
     */
    public function getRouteApp()
    {
        return $this->routeGroupApp;
    }

    /**
     * 获取所有的匹配字符串
     * @return array 匹配字符串
     */
    public function getPattern()
    {
        return $this->_patterns;
    }

    /**
     * 返回本次路由到的类和方法,如果是直接callable的方法则会返回[null,null]
     * @return array 匹配到的类和方法
     */
    public function getRouteClassMethod()
    {
        return [$this->routeClass, $this->routeMethod];
    }

    /**
     * 匹配URL方法
     * @param  $method
     * @param  $url
     * @return \Generator
     */
    private function match($method, $url)
    {
        $patterns = $this->_patterns;
        $matches = array();
        $vitex = Vitex::getInstance();
        $req = $vitex->req;
        $req->path = $url;
        $url = trim($url, '/');
        if (!$url) {
            $url = '/';
        }
        //保存请求信息

        $req->route = [
            'url' => $url,
            'method' => $method,
        ];
        //指定的方法
        /**
         * 请求方法
         * 路由匹配正则
         * 执行方法
         * 原始路由字符串
         * 路由应用
         * 路由别名
         * 方法
         */
        foreach ($patterns as list($_method, $pattern, $call, $oriPattern, $appName, $alias, $methods)) {
            if ($method !== $_method && $_method !== 'ALL' && $_method !== 'INVOKE') {
                continue;
            }
            $req->route['matchUrl'] = $pattern;
            $req->route['matchMethod'] = $method;
            $req->route['matchPattern'] = $oriPattern;
            if (preg_match($pattern, $url, $matches)) {
                //设置url匹配的分段信息
                $vitex->req->params = $this->_parseParams($matches);
                //call
                if (is_string($call)) {
                    //创建对象
                    $call = $this->getCallable($call, $method, $appName, $methods);
                    if (!$call) {
                        continue;
                    }
                }
                yield $call;
            }
        }
    }

    /**
     * 根据路由信息实例化相应的控制器类来返回函数方法对象
     * @param  string $str 字符串
     * @param  string $httpmethod http请求的方法
     * @param string $appName 路由注册的应用
     * @return callable 可执行的方法
     */
    public function getCallable($str, $httpmethod, $appName = '', $routeMethods)
    {
        $strs = explode('@', $str);
        $class = array_shift($strs);
        $method = strtolower($strs ? array_pop($strs) : $httpmethod);
        $vitex = Vitex::getInstance();

        //完全限定命名空间
        if ($class[0] != '\\') {
            //当前应用
            $app = $appName ?: $vitex->appName;
            $class = '\\' . $app . '\\controller\\' . $class;
        }

        $this->routeClass = $class;
        $this->routeMethod = $method;
        //$obj = new $class;
        $obj  = $vitex->container->get($class);
        if (!$obj || !method_exists($obj, $method)) {
            Vitex::getInstance()->log->error('Class:' . $class . '->' . $method . ' Not Found!!');
            return null;
        }
        /**
         * 返回一个可执行的闭包
         */
        return function () use ($obj, $method, $routeMethods) {
            /**
             * 路由前执行的方法
             */
            $beforeData = null;
            if (isset($routeMethods['before'])) {
                $beforeData = call_user_func_array($routeMethods['before'], [Request::getInstance(), Response::getInstance()]);
            }

            /**
             * 包裹路由执行
             */
            if (isset($routeMethods['wrap'])) {
                $result = call_user_func_array($routeMethods['wrap'], [function () use ($obj, $method) {
                    return $obj->{$method}();
                }, Request::getInstance(), Response::getInstance(),$beforeData]);
            } else {
                $result = $obj->{$method}();
            }

            /**
             * 路由后执行的方法
             */
            if (isset($routeMethods['after'])) {
                call_user_func_array($routeMethods['after'], [Request::getInstance(), Response::getInstance(),$result]);
            }
            return $result;
        };
    }

    /**
     * 匹配URL匹配信息
     * @internal param array $params 匹配的URL段
     * @param  array $matches
     * @return object
     */
    public function _parseParams(array $matches)
    {
        $params = array();
        foreach ($matches as $k => $v) {
            if (is_numeric($k)) {
                if ($k === 0) {
                    continue;
                }
                $params[] = $v;
            } else {
                $params[$k] = $v;
            }
        }
        return new Set($params);
    }

}
