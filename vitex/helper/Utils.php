<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex\helper;

use vitex\core\Exception;

/**
 * 一些常用的辅助方法，如加密解密
 */
class Utils
{
    const CRYPT_DEFAULT = ['algorithm' => MCRYPT_RIJNDAEL_256, 'mode' => MCRYPT_MODE_CBC];
    /**
     * 加密数据
     * @param  string $data 要加密的数据
     * @param  string $key 加密的密钥
     * @param  array $setting 配置文件，配置加密的模式和加密的方法
     * @return string  加密的数据
     */
    public static function encrypt($data, $key, array $setting = [])
    {
        $data    = serialize($data);
        $setting = array_merge(Utils::CRYPT_DEFAULT, $setting);
        /* 打开加密模块，并且创建初始向量 */
        $mmo = mcrypt_module_open($setting['algorithm'], '', $setting['mode'], '');
        $key = substr($key, 0, mcrypt_enc_get_key_size($mmo));
        //创建加密向量
        $iv_size = mcrypt_enc_get_iv_size($mmo);
        $iv      = mcrypt_create_iv($iv_size);
        /* 初始化加密句柄 */
        mcrypt_generic_init($mmo, $key, $iv);
        /* 加密数据 */
        $value = mcrypt_generic($mmo, $data);
        mcrypt_generic_deinit($mmo);
        mcrypt_module_close($mmo);
        $iv    = base64_encode($iv);
        $value = base64_encode($value);
        $hmac  = hash_hmac('sha256', $iv.$value, $key);
        $json  = json_encode(compact('iv', 'value','hmac'));
        return base64_encode($json);
    }

    /**
     * 解密数据
     * @param  string $endata 加密的字符串
     * @param  string $key 密钥
     * @param  array $setting 配置
     * @return mixed 加密的数据
     * @throws Exception
     */
    public static function decrypt($endata, $key, array $setting = [])
    {
        $data = json_decode(base64_decode($endata), true);
        if (!is_array($data) || !isset($data['iv'], $data['value'], $data['hmac'])) {
            throw new Exception("无法解密数据");
        }
        $hmac  = $data['hmac'];
        $iv    = base64_decode($data['iv']);
        $value = base64_decode($data['value']);

        //验证 hmac
        $checkKey  = substr(md5(time()),12,16);
        $calcHmac  = hash_hmac('sha256', hash_hmac('sha256', $iv.$value, $key), $checkKey, true);

        if(hash_equals(hash_hmac('sha256', $hmac, $checkKey, true), $calcHmac)) {
            throw new Exception("无法解密数据");
        }

        $setting = array_merge(Utils::CRYPT_DEFAULT, $setting);
        /* 打开加密模块，并且创建初始向量 */
        $mmo = mcrypt_module_open($setting['algorithm'], '', $setting['mode'], '');
        $key = substr($key, 0, mcrypt_enc_get_key_size($mmo));

        /* 初始化加密句柄 */
        mcrypt_generic_init($mmo, $key, $iv);
        $dedata = mdecrypt_generic($mmo, $value);
        mcrypt_generic_deinit($mmo);
        mcrypt_module_close($mmo);
        return unserialize($dedata);
    }
}
