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

use Vitex\Helper\Set;
use Vitex\Vitex;

/**
 * 路由记录器类，用于记录各种路由中间件的对应关系，并且完成URl和方法的匹配
 */
class Router
{
    private $_patterns = [];
    protected $env;
    protected $vitex         = null;
    protected $caseSensitive = false;
    protected $regexps       = [];
    protected $cacheBaseurl  = null;
    public function __construct()
    {
        $this->env = Env::getInstance();
        $this->setRegexp([
            'digit'      => '[0-9]+',
            'alpha'      => '[a-zA-Z]+',
            'alphadigit' => '[0-9a-zA-Z]+',
            'float'      => '[0-9]+\.{1}[0-9]+',
        ]);
    }

    /**
     * 设置预支的正则表达式
     * @param mixed $name 名称/或者关联数组
     * @param string $regexp 正则
     * @return $this
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
     * @return string 值
     */
    public function getRegexp($name = null)
    {
        if ($name === null) {
            return $this->regexps;
        }
        return isset($this->regexps[$name]) ? $this->regexps[$name] : '[^/]+';
    }
    /**
     * 根据指定的参数生成url地址
     * @param  string $url                    路有段，如果是个有效的Url则会直接返回
     * @param  array  $params                 参数段，会被转为 querystring
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
            $vitex   = Vitex::getInstance();
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
     * @param  String      $letter 字符
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
     * @param  string $matcher          分组路径
     * @return array  匹配的分组
     */
    public function getSlice($matcher)
    {
        $len    = strlen($matcher);
        $temp   = '';
        $start  = null;
        $slices = [];
        for ($i = 0; $i < $len; $i++) {
            $letter = $matcher[$i];
            if ($letter == ':') {
                $start = $i;
                continue;
            }

            if ($start !== null && $this->isValid($letter)) {
                $temp .= $letter;
            }

            if (!$this->isValid($letter) && $start !== null) {
                $slices[] = [$temp, "(?<" . $temp . ">[^/]+)", $start, $i];
                $start    = null;
                $temp     = '';
            }
        }
        if ($temp) {
            $regexp = '[^/]+';
            $name   = $temp;
            if (strpos($temp, '@') !== false) {
                list($name, $regexpKey) = explode('@', $temp);
                $regexp                 = $this->getRegexp($regexpKey);
            }
            $slices[] = [$temp, "(?<" . $name . ">" . $regexp . ")", $start, $i];
        }

        return $slices;
    }

    /**
     * 注册映射一个请求参数
     * @param string $method 请求方法
     * @param string $pattern 匹配参数
     * @param mixed $call 执行的方法
     * @return $this
     */

    public function map($method, $pattern, $call)
    {
        if ($this->vitex === null) {
            $this->vitex         = Vitex::getInstance();
            $this->caseSensitive = $this->vitex->getConfig('router.case_sensitive');
        }

        $matcher = $pattern;
        $method  = strtoupper($method);
        $matcher = trim($matcher, '/');
        $cases   = $this->caseSensitive ? '' : 'i';

        if (!$matcher) {
            $matcher = '|^/$|';
        } elseif ($matcher === '*') {
            $matcher = '|^.*$|' . $cases;
        } elseif ($matcher[0] == '|') {
            //正则表达式
            $matcher = $matcher . $cases;
        } else {
            //替换 *为匹配除了 /分组之外的所有内容
            $matcher = str_replace(['*', '?'], ['([^\/]*)', '([^\/]?)'], $matcher);
            $slices  = $this->getSlice($matcher);
            foreach ($slices as list($slice, $reg)) {
                $matcher = str_replace(':' . $slice, $reg, $matcher);
            }
            $matcher = '|^' . $matcher . '$|' . $cases;
        }
        $this->_patterns[] = [$method, $matcher, $call, $pattern];
        return $this;
    }

    /**
     * 获取匹配的路由结果
     * @return \Generator [description]
     */
    public function getRouter()
    {
        $method = strtoupper($this->env->method());
        $url    = $this->env->getPathinfo();
        //默认首页
        $url = rtrim($url, '/');
        $url = $url ? $url : '/';
        return $this->match($method, $url);
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
     * 匹配URL方法
     * @param $method
     * @param $url
     * @return \Generator
     */
    private function match($method, $url)
    {
        $patterns  = $this->_patterns;
        $matches   = array();
        $vitex     = Vitex::getInstance();
        $req       = $vitex->req;
        $req->path = $url;
        $url       = trim($url, '/');
        if (!$url) {
            $url = '/';
        }
        //保存请求信息

        $req->route = [
            'url'    => $url,
            'method' => $method,
        ];
        //指定的方法
        foreach ($patterns as list($_method, $pattern, $call)) {
            if ($method !== $_method && $_method !== 'ALL' && $_method !== 'INVOKE') {
                continue;
            }
            $req->route['matchUrl']    = $pattern;
            $req->route['matchMethod'] = $method;
            if (preg_match($pattern, $url, $matches)) {
                //设置url匹配的分段信息
                $vitex->req->params = $this->_parseParams($matches);
                //call
                if (is_string($call)) {
                    //创建对象
                    $call = $this->getCallable($call, $method);
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
     * @param  string $str                 字符串
     * @param  string $httpmethod          http请求的方法
     * @return mixed  可执行的方法
     */
    public function getCallable($str, $httpmethod)
    {
        $strs   = explode('@', $str);
        $class  = array_shift($strs);
        $method = strtolower($strs ? array_pop($strs) : $httpmethod);
        //完全限定命名空间
        if ($class[0] != '\\') {
            //当前应用
            $vitex = Vitex::getInstance();
            $app   = $vitex->appName;
            $class = '\\' . ucfirst($app) . '\\Controller\\' . $class;
        }
        $obj = new $class;
        if (!$obj || !method_exists($obj, $method)) {
            Vitex::getInstance()->log->error('Class:' . $class . '->' . $method . ' Not Found!!');
            return false;
        }
        return function () use ($obj, $method) {return $obj->{$method}();};
    }

    /**
     * 匹配URL匹配信息
     * @param array $matches
     * @return object
     * @internal param array $params 匹配的URL段
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
