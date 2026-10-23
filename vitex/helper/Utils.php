<?php declare(strict_types=1);
/**
 * Vitex 一个基于php7.0开发的 快速开发restful API的微型框架
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
    const CRYPT_DEFAULT = ['cipher' => 'aes-256-cbc', 'options' => OPENSSL_RAW_DATA];

    /**
     * 旧版(固定IV)加密的默认配置，仅供废弃的encrypt/decrypt兼容使用
     */
    const CRYPT_DEFAULT_LEGACY = ['cipher' => 'aes-256-cbc', 'options' => OPENSSL_RAW_DATA, 'iv' => '0000000000000000'];

    /**
     * 密文存储格式: base64( IV + HMAC-SHA256(iv.ciphertext) + ciphertext )
     * IV每次加密随机生成并前置在密文中，解密时按算法IV长度从头部切出，
     * 因此解密方无需预先知道IV(IV不要求保密，只要求每次加密不可预测且不重复)
     */
    const MAC_ALGO = 'sha256';
    /** HMAC-SHA256 输出32字节 */
    const MAC_LENGTH = 32;
    /** 由用户密钥派生出相互独立的加密密钥与MAC密钥(Encrypt-then-MAC) */
    const KDF_ENC = 'vitex.crypt.enc';
    const KDF_MAC = 'vitex.crypt.mac';

    /**
     * 历史版本硬编码的公开默认cookie密钥，已知不安全，配置了该值时加密组件拒绝工作
     */
    const KNOWN_INSECURE_KEY = 'Vitex is a micro restfull framework';

    /**
     * 校验cookie加密密钥：禁止空值与框架公开默认密钥(fail-closed)，
     * 防止使用公开密钥的应用被离线伪造密文(会话/加密cookie身份伪造)
     * @param string $secret_key
     * @throws Exception
     */
    public static function assertSecretKey($secret_key)
    {
        $secret_key = (string)$secret_key;
        if ($secret_key === '' || $secret_key === self::KNOWN_INSECURE_KEY) {
            throw new Exception('cookie加密密钥未配置或使用了框架公开默认值，请配置独立的随机密钥(cookies.secret_key)');
        }
    }

    /**
     * 安全加密数据
     * 每次加密使用随机IV并前置密文，附带HMAC做完整性校验，
     * 防止密文比特翻转篡改、填充预言机攻击与相同明文前缀产生相同密文
     * @param  string $data 要加密的数据
     * @param  string $key 加密的密钥
     * @param  array $setting 配置文件，配置加密的模式和加密的方法; setting['iv'] 显式传入时使用固定IV(不推荐，仅兼容特殊场景)
     * @return string 加密的数据
     * @throws Exception
     */
    public static function encryptSafe($data, $key, array $setting = [])
    {
        $data = (string) $data;
        if(!is_string($data)){
            throw  new Exception(Exception::CODE_PARAM_ERROR_MSG,Exception::CODE_PARAM_VALUE_ERROR);
        }

        $setting = array_merge(Utils::CRYPT_DEFAULT, $setting);
        $cipher = $setting['cipher'];
        $ivLength = openssl_cipher_iv_length($cipher);
        if ($ivLength === false || $ivLength <= 0) {
            throw new Exception('Can not encrypt data: invalid cipher ' . $cipher, Exception::CODE_PARAM_VALUE_ERROR);
        }
        //默认每次随机生成IV；仅当调用方显式传入时使用固定IV(不推荐)
        $iv = !empty($setting['iv']) ? (string)$setting['iv'] : random_bytes($ivLength);
        if (strlen($iv) !== $ivLength) {
            throw new Exception('Can not encrypt data: iv length error', Exception::CODE_PARAM_VALUE_ERROR);
        }
        list($encKey, $macKey) = self::deriveKeys((string)$key);
        $encrypted = openssl_encrypt($data, $cipher, $encKey, $setting['options'], $iv);
        if($encrypted === false){
            throw new Exception('Can not encrypt data',Exception::CODE_PARAM_VALUE_ERROR);
        }
        //Encrypt-then-MAC: 对IV+密文整体计算HMAC，解密前校验
        $mac = hash_hmac(self::MAC_ALGO, $iv . $encrypted, $macKey, true);
        return base64_encode($iv . $mac . $encrypted);
    }

    /**
     * 安全解密数据
     * 从密文头部提取IV与HMAC，先校验HMAC(hash_equals防时序攻击)再解密
     * @param  string $endata 加密的字符串
     * @param  string $key 密钥
     * @param  array $setting 配置
     * @return mixed 加密的数据
     * @throws Exception
     */
    public static function decryptSafe($endata, $key, array $setting = [])
    {
        if(!is_string($endata)){
            throw  new Exception(Exception::CODE_PARAM_ERROR_MSG,Exception::CODE_PARAM_VALUE_ERROR);
        }
        $setting = array_merge(Utils::CRYPT_DEFAULT, $setting);
        $cipher = $setting['cipher'];
        $ivLength = openssl_cipher_iv_length($cipher);
        if ($ivLength === false || $ivLength <= 0) {
            throw new Exception('Can not decrypt data: invalid cipher ' . $cipher, Exception::CODE_PARAM_VALUE_ERROR);
        }
        $raw = base64_decode($endata, true);
        if ($raw === false || strlen($raw) < $ivLength + self::MAC_LENGTH + 1) {
            throw new Exception('Can not decrypt data', Exception::CODE_PARAM_VALUE_ERROR);
        }
        $iv = substr($raw, 0, $ivLength);
        $mac = substr($raw, $ivLength, self::MAC_LENGTH);
        $encrypted = substr($raw, $ivLength + self::MAC_LENGTH);
        //先验证MAC再解密(Encrypt-then-MAC)
        list($encKey, $macKey) = self::deriveKeys((string)$key);
        $expected = hash_hmac(self::MAC_ALGO, $iv . $encrypted, $macKey, true);
        if (!hash_equals($expected, $mac)) {
            throw new Exception('Can not decrypt data', Exception::CODE_PARAM_VALUE_ERROR);
        }
        $data = openssl_decrypt($encrypted, $cipher, $encKey, $setting['options'], $iv);
        if($data === false){
            throw new Exception('Can not decrypt data',Exception::CODE_PARAM_VALUE_ERROR);
        }
        return $data;
    }

    /**
     * 从用户密钥派生独立的加密密钥与MAC密钥
     * @param string $key
     * @return array [encKey, macKey] 均为32字节二进制
     */
    private static function deriveKeys(string $key): array
    {
        return [
            hash_hmac(self::MAC_ALGO, self::KDF_ENC, $key, true),
            hash_hmac(self::MAC_ALGO, self::KDF_MAC, $key, true),
        ];
    }

    /**
     * 加密数据(旧版兼容)
     * 使用固定IV加密，相同明文与密钥总是产生相同密文，且无完整性校验，存在安全隐患
     * 为了兼容旧数据保留此方法，请尽快迁移到随机IV的 encryptSafe()
     * @param  string $data 要加密的数据
     * @param  string $key 加密的密钥
     * @param  array $setting 配置文件，配置加密的模式和加密的方法，setting['iv']为固定IV
     * @return string 加密的数据
     * @throws Exception
     * @deprecated 1.1.8 请使用 self::encryptSafe()
     */
    public static function encrypt($data, $key, array $setting = [])
    {
        $data = (string) $data;
        if(!is_string($data)){
            throw  new Exception(Exception::CODE_PARAM_ERROR_MSG,Exception::CODE_PARAM_VALUE_ERROR);
        }

        $setting = array_merge(Utils::CRYPT_DEFAULT_LEGACY, $setting);
        $encrypted = openssl_encrypt($data,$setting['cipher'],$key,$setting['options'],$setting['iv']);
        if($encrypted === false){
            throw new Exception('Can not encrypt data',Exception::CODE_PARAM_VALUE_ERROR);
        }
        return base64_encode($encrypted);
    }

    /**
     * 解密数据(旧版兼容)
     * 使用固定IV解密旧版encrypt()加密的数据，无完整性校验
     * 为了兼容旧数据保留此方法，请尽快迁移到随机IV的 decryptSafe()
     * @param  string $endata 加密的字符串
     * @param  string $key 密钥
     * @param  array $setting 配置
     * @return mixed 加密的数据
     * @throws Exception
     * @deprecated 1.1.8 请使用 self::decryptSafe()
     */
    public static function decrypt($endata, $key, array $setting = [])
    {
        if(!is_string($endata)){
            throw  new Exception(Exception::CODE_PARAM_ERROR_MSG,Exception::CODE_PARAM_VALUE_ERROR);
        }
        $setting = array_merge(Utils::CRYPT_DEFAULT_LEGACY, $setting);
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
