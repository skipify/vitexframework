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
     * 根据指定的cookie名获取cookie
     * @param  string $name                                                       cookie的名字
     * @return string|array cookie的值，如果有加密返回的是解密后的值
     */
    public function getCookie($name = null)
    {
        $cookie  = $_COOKIE;
        $encrypt = $this->vitex->getConfig('cookies.encrypt');
        if ($encrypt) {
            $secret_key = $this->vitex->getConfig('cookies.secret_key');
            foreach ($cookie as &$c) {
                try {
                    $c = Utils::decrypt($c, $secret_key);
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
