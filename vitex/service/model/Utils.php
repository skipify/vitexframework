<?php
/**
 * Vitex 一个基于php8.0开发的 快速开发restful API的微型框架
 * @version  2.0.0
 *
 * @package vitex\service\model
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex\service\model;

/**
 * 工具类
 * @package vitex\service\model
 */

class Utils
{
    /**
     * 驼峰命名转下划线命名
     * 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
     */
    public static function snakeCase($name)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1_$2", $name));
    }

    /**
     * 下划线转驼峰
     * step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
     * step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
     */
    public static function camelCase($name)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($name))));
    }

}