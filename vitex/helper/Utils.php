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

namespace vitex\helper;

/**
 * 一些常用的辅助方法，如加密解密
 */
class Utils
{
    /**
     * 加密数据
     * @param  string $data                         要加密的数据
     * @param  string $key                          加密的密钥
     * @param  string $iv                           向量值
     * @param  array  $setting                      配置文件，配置加密的模式和加密的方法
     * @return array  加密的数据和向量值
     */
    public static function encrypt($data, $key, $iv = null, array $setting = [])
    {
        $default = ['algorithm' => MCRYPT_RIJNDAEL_256, 'mode' => MCRYPT_MODE_CBC];
        $setting = array_merge($default, $setting);
        /* 打开加密模块，并且创建初始向量 */
        $mmo     = mcrypt_module_open($setting['algorithm'], '', $setting['mode'], '');
        $key     = substr($key, 0, mcrypt_enc_get_key_size($mmo));
        $iv_size = mcrypt_enc_get_iv_size($mmo);
        if ($iv) {
            if ($iv_size < strlen($iv)) {
                $iv = substr($iv, 0, $iv_size);
            }
        } else {
            $iv = mcrypt_create_iv($iv_size);
        }

        /* 初始化加密句柄 */
        mcrypt_generic_init($mmo, $key, $iv);
        /* 加密数据 */
        $endata = mcrypt_generic($mmo, $data);
        mcrypt_generic_deinit($mmo);
        mcrypt_module_close($mmo);
        return [$endata, $iv];
    }

    /**
     * 解密数据
     * @param  string $endata           加密的字符串
     * @param  string $key              密钥
     * @param  string $iv               向量值
     * @param  array  $setting          配置
     * @return string 加密的数据
     */
    public static function decrypt($endata, $key, $iv, array $setting = [])
    {
        $default = ['algorithm' => MCRYPT_RIJNDAEL_256, 'mode' => MCRYPT_MODE_CBC];
        $setting = array_merge($default, $setting);
        /* 打开加密模块，并且创建初始向量 */
        $mmo     = mcrypt_module_open($setting['algorithm'], '', $setting['mode'], '');
        $key     = substr($key, 0, mcrypt_enc_get_key_size($mmo));
        $iv_size = mcrypt_enc_get_iv_size($mmo);

        if ($iv_size < strlen($iv)) {
            $iv = substr($iv, 0, $iv_size);
        }
        /* 初始化加密句柄 */
        mcrypt_generic_init($mmo, $key, $iv);
        $data = mdecrypt_generic($mmo, $endata);
        mcrypt_generic_deinit($mmo);
        mcrypt_module_close($mmo);
        return $data;
    }
}
