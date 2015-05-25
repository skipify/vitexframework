<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package Vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
namespace Vitex;

/**
 * 用于模板展示的View类
 */
class View
{

    protected $data;
    /**
     * 传递给模板的数据
     * @var array
     */
    protected $tpldata;
    private $templatepath;
    public $vitex;
    public $tplext;
    public function __construct()
    {
        $this->data   = new Helper\Set();
        $this->vitex  = Vitex::getInstance();
        $this->tplext = $this->vitex->getConfig('templates.ext');
        $this->setTplPath($this->vitex->getConfig('templates.path'));
    }

    /**
     * 给模板传递变量
     * @param string $key 键值
     * @param string $val 键名
     */
    public function set($key, $val = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->data[$k] = $v;
            }
        } else {
            $this->data[$key] = $val;
        }
        return $this;
    }

    /**
     * 获取数据
     * @param  string $key              键值
     * @return mixed  要返回的值
     */
    public function get($key = null)
    {
        if ($key === null) {
            return $this->data->all();
        }
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * 获取当前显示的模板所在路径
     * @param  string $tpl     模板路径名字
     * @return string $path;
     */
    public function getTplPath()
    {
        return $this->templatepath;
    }

    /**
     * 设置/获取当前显示的模板所在路径
     * @param  string $tpl     模板路径名字
     * @return object $this;
     */
    public function setTplPath($tplpath)
    {
        $this->templatepath = rtrim($tplpath, DIRECTORY_SEPARATOR);
        return $this;
    }

    /**s
     * 获取当前显示的模板所在的真实路径
     * @param  string $tpl     模板路径名字
     * @return object $this;
     */
    public function template($tpl)
    {
        $extlen = strlen($this->tplext);
        if (substr($tpl, -$extlen) !== $this->tplext) {
            $tpl .= $this->tplext;
        }
        return $this->getTplPath() . DIRECTORY_SEPARATOR . $tpl;
    }

    /**
     * 返回解析的模板数据
     * @param  string $tplname             模板名称
     * @param  array  $data                数据
     * @return string 解析后的数据
     */
    public function fetch($tplname, array $data = [], $merge = true)
    {
        if ($merge) {
            $locals        = $this->vitex->res->locals->all();
            $data          = array_merge($locals, $this->get(), $data);
            $this->tpldata = $data;
        }
        $file = $this->template($tplname);
        if (!file_exists($file)) {
            throw new \Exception("模板文件--" . $file . '--不存在');
        }
        extract($data, EXTR_OVERWRITE);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * 定义一个模板中可以用的URL构造函数
     * @param  string $url           url段
     * @param  array  $params        关联数组
     * @return string 链接地址
     */
    public function url($url, $params = [])
    {
        return $this->vitex->url($url, $params);
    }

    /**
     * 装载模板,本方法一般用于模板嵌套中使用，数据如果不添加则在子模板中仍然使用父模板中一样的数据
     * @param  string  $tplname 模板名称
     * @param  array   $data    附加的数据
     * @param  boolean $merge   是否要重新合并数据
     * @return void
     */
    public function render($tplname, array $data = [], $merge = false)
    {
        $data = array_merge($this->tpldata, $data);
        echo $this->fetch($tplname, $data, $merge);
    }

    /**
     * 展示模板内容，直接输出
     * @param  stirng $tplname 模板名称
     * @param  array  $data    数据
     * @return void
     */
    public function display($tplname, array $data = [])
    {
        echo $this->fetch($tplname, $data);
    }

}
