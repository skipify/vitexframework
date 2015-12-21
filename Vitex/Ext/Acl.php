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
namespace Vitex\Ext;

use Vitex\Core\Router;

class Acl
{
    private $rules = [];
    private $group = [];
    public function __construct()
    {
        $this->route = new Router();
    }

    /**
     * 添加匹配规则
     * @param string /array $pattern 规则多个请传数组
     * @param string $method 请求方法默认为all
     * @return self
     */
    public function addRule($pattern, $method = 'all')
    {
        if (is_array($pattern)) {
            foreach ($pattern as $_pattern) {
                $this->addRule($_pattern, $method);
            }
            return $this;
        }
        //解析URL段
        $this->rules[$pattern] = [$method, $this->parseRule($pattern)];
        return $this;
    }

    /**
     * 兼容Route的模式解析规则
     * @param  string $pattern             URL规则
     * @return string 解析后的规则
     */
    private function parseRule($pattern)
    {
        $matcher = $pattern;
        $matcher = trim($matcher, '/');
        if (!$matcher) {
            return '|^/$|';
        }
        if ($matcher === '*') {
            return '|^.*$|i';
        } else {
            //替换 *为匹配除了 /分组之外的所有内容
            $matcher = str_replace('*', '[^\/]*', $matcher);
        }

        $slices = $this->route->getSlice($matcher);
        foreach ($slices as list($slice, $reg)) {
            $matcher = str_replace(':' . $slice, $reg, $matcher);
        }
        $matcher = '|^' . $matcher . '$|i';
        return $matcher;
    }

    /**
     * 获取当前的权限规则
     * @return array 规则列表
     */
    public function getRule()
    {
        return $this->rules;
    }

    /**
     * 清空所有权限
     * @return self
     */
    public function clearRule()
    {
        $this->rules = [];
        return $this;
    }

    /**
     * 判断当前指定的URL是否通过权限检测
     * @param  string  $url                 URL
     * @return boolean 规则是否匹配
     */
    public function isAllowed($url, $method = 'all')
    {
        $url = trim($url, '/');
        foreach ($this->rules as list($_method, $rule)) {
            if (($method == 'all' || $method == $_method) && preg_match($rule, $url, $matches)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 只要有一个规则满足即可
     * @param  不定参数
     * @return bool
     */
    public function anyAllowed($rules, $method = "all")
    {
        foreach ($rules as $rule) {
            if ($this->isAllowed($rule, $method)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 添加一个子规则对象，把目标对象规则合并到当前对象中
     * @param  \Vitex\Ext\Acl $child         子对象
     * @return self         当前对象
     */
    public function addChild(\Vitex\Ext\Acl $child)
    {
        $rules = $child->getRule();
        foreach ($rules as $rule) {
            $this->rules[] = $rule;
        }
        return $this;
    }

    /**
     * 按照组添加一组整体的权限
     * 注意分组的权限也会被加入到当前对象的权限中去
     *
     * @param  string    $alias  规则名
     * @param  array $rules  array
     * @return self
     */
    public function addGroup($alias, $rules, $method = "all")
    {
        $this->addRule($rules, $method);
        $this->group[$alias] = (new self)->addRule($rules, $method);
        return $this;
    }

    /**
     * 获取分组权限
     * @param  分组名称
     * @return array          分组权限
     */
    public function getGroupRule($alias)
    {
        return isset($this->group[$alias]) ? $this->group[$alias] : [];
    }

    /**
     * 获取分组信息
     * @return array 分组数组
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * 检查是否有分组权限
     * @param  string $alias         分组名
     * @return bool   是否通过
     */
    public function groupAllowed($alias, $pattern, $method = 'all')
    {
        if (isset($this->group[$alias])) {
            return false;
        }
        $rule = $this->group[$alias];
        return $rule->isAllowed($pattern, $method);
    }

}
