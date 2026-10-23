<?php declare(strict_types=1);
/**
 * Vitex 一个基于php8.0开发的 快速开发restful API的微型框架
 * @version  2.0.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
namespace vitex\middleware;

use vitex\helper\Set;
use vitex\helper\Utils;
use vitex\Middleware;
use vitex\service\config\CookieConfig;
use vitex\core\Exception;

/**
 * cookie中间件，用于把Cookie信息附加到req对象中
 */
class Cookie extends Middleware
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取cookie加密配置
     * 优先读取3.0的cookie配置组，兼容旧版的cookies.*顶层配置
     * @return array [encrypt, secret_key]
     * @throws \InvalidArgumentException
     */
    private function getEncryptConfig(): array
    {
        $config = $this->vitex->getConfig('cookie');
        if (is_array($config) && $config) {
            $cookieConfig = CookieConfig::fromArray($config);
            $encrypt = $cookieConfig->isEncrypt();
            $secret_key = $cookieConfig->getSecretKey();
        } else {
            //兼容旧版点分隔的顶层配置
            $encrypt = (bool)$this->vitex->getConfig('cookies.encrypt');
            $secret_key = '';
        }
        //配置组密钥为空时回退旧版顶层配置
        if ($secret_key === '') {
            $secret_key = (string)$this->vitex->getConfig('cookies.secret_key');
        }
        //启用加密时禁止空密钥与框架公开默认密钥(fail-closed)
        if ($encrypt) {
            CookieConfig::assertSecretKey($secret_key);
        }
        return [$encrypt, $secret_key];
    }

    /**
     * 根据指定的cookie名获取cookie
     * @param  string $name                                                       cookie的名字
     * @return string|array cookie的值，如果有加密返回的是解密后的值
     */
    public function getCookie($name = null)
    {
        $cookie  = $_COOKIE;
        list($encrypt, $secret_key) = $this->getEncryptConfig();
        if ($encrypt && $secret_key) {
            foreach ($cookie as &$c) {
                try {
                    $c = Utils::decryptSafe($c, $secret_key);
                } catch(Exception $e){}
            }
        }
        if ($name === null) {
            return $cookie;
        }
        return isset($cookie[$name]) ? $cookie[$name] : null;
    }

    /**
     * 调用中间件
     */
    public function call()
    {
        $cookie                    = $this->getCookie();
        $this->vitex->req->cookies = new Set($cookie ?? []);
        $this->runNext();
    }
}
