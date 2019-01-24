<?php declare(strict_types=1);
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
    const CRYPT_DEFAULT = ['cipher' => 'aes-256-cbc', 'options' => OPENSSL_RAW_DATA,'iv'=>'0000000000000000'];

    /**
     * 加密数据
     * @param  string $data 要加密的数据
     * @param  string $key 加密的密钥
     * @param  array $setting 配置文件，配置加密的模式和加密的方法
     * @return string 加密的数据
     * @throws Exception
     */
    public static function encrypt($data, $key, array $setting = [])
    {
        $data = (string) $data;
        if(!is_string($data)){
            throw  new Exception(Exception::CODE_PARAM_ERROR_MSG,Exception::CODE_PARAM_VALUE_ERROR);
        }

        $setting = array_merge(Utils::CRYPT_DEFAULT, $setting);
        $encrypted = openssl_encrypt($data,$setting['cipher'],$key,$setting['options'],$setting['iv']);
        if($encrypted === false){
            throw new Exception('Can not encrypt data',Exception::CODE_PARAM_VALUE_ERROR);
        }
        return base64_encode($encrypted);
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
        if(!is_string($endata)){
            throw  new Exception(Exception::CODE_PARAM_ERROR_MSG,Exception::CODE_PARAM_VALUE_ERROR);
        }
        $setting = array_merge(Utils::CRYPT_DEFAULT, $setting);
        $data = openssl_decrypt(base64_decode($endata), $setting['cipher'], $key, $setting['options'],$setting['iv']);
        if($data === false){
            throw new Exception('Can not decrypt data',Exception::CODE_PARAM_VALUE_ERROR);
        }
        return $data;
    }

    /**
     * 判断PHP版本是否>=某一个版本
     * @param $version
     * @return bool
     */
    public static function phpVersion($version)
    {
        static $phpVerisons = [];

        $version = (string)$version;

        if (!isset($phpVerisons[$version])) {
            $phpVerisons[$version] = version_compare(PHP_VERSION, $version, '>=');
        }
        return $phpVerisons[$version];
    }
}
