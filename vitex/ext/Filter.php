<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.3.0
 *
 * @package vitex/ext
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
/**
 * 用于数据过滤,不做验证使用,仅仅过滤掉不符合条件的数据,因此此数据可能与原始数据不同
 * 例如:  asd__sadajsbd123 使用 alnum过滤则仅会返回asdsadajsbd123
 */

namespace vitex\ext;


use vitex\core\Exception;

class Filter
{
    const FILTER_ALNUM  = 'alnum';
    const FILTER_ALPHA  = 'alpha';
    const FILTER_NUMBER = 'number';
    const FILTER_INT    = 'int';
    const FILTER_SAFE   = 'safe';
    const FILTER_ADDSLASHES = 'addslashes';


    public static function factory($str, $type)
    {
        if (method_exists(get_class(), $type)) {
            return self::$type($str);
        }
        throw new Exception("不存在的过滤方法:" . $type);
    }

    /**
     * 只返回字母数字
     * @param $str
     * @return mixed
     */
    public static function alnum($str)
    {
        return preg_replace('/[^0-9a-z]/i', '', $str);
    }

    /**
     * 只返回字母
     * @param $str
     * @return mixed
     */
    public static function alpha($str)
    {
        return preg_replace('/[^a-z]/i', '', $str);

    }

    /**
     * 仅返回数字类型
     * @param $str
     * @return mixed
     */
    public static function number($str)
    {
        return filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND | FILTER_FLAG_ALLOW_SCIENTIFIC);
    }

    /**
     * 返回整形数据,只要是数字即可,不包含16进制,开头可以是0
     * @param $str
     * @return mixed
     */
    public static function int($str)
    {
        return preg_replace('/[^0-9]/', '', $str);
    }

    /**
     * 转义  <> '"&字符
     * @param $str
     * @return string
     */
    public static function safe($str)
    {
        return htmlspecialchars($str, ENT_QUOTES);
    }

    /**
     * 给引号加斜线转义
     * @param $str
     * @return string
     */
    public static function addslashes($str)
    {
        return addslashes($str);
    }

}