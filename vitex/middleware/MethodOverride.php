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

use vitex\Middleware;

/**
 * 请求方法重写的类，可以使用此方法通过代理重写，或者直接客户端通过参数重写方法
 */
class MethodOverride extends Middleware
{
    protected $overrideKey;

    /**
     * 允许重写的HTTP动词白名单
     * 白名单之外的值(包括任意自定义字符串)一律忽略并保持原请求方法，
     * 防止把任意方法名注入路由解析(Router会将请求动词作为无@method路由的默认方法名)，
     * 以及把请求洗成危险动词绕过CSRF等方法维度的校验
     */
    private const OVERRIDE_ALLOWED = ['PUT', 'PATCH', 'DELETE'];

    public function call()
    {
        $env               = $this->vitex->env;
        $this->overrideKey = $this->vitex->getConfig('methodoverride.key');

        if ($env->get('HTTP_X_HTTP_METHOD_OVERRIDE')) {
            $override = strtoupper(trim((string)$env->get('HTTP_X_HTTP_METHOD_OVERRIDE')));
            if (in_array($override, self::OVERRIDE_ALLOWED, true)) {
                $env['ORIGINAL_METHOD'] = $env['REQUEST_METHOD'];
                $env['REQUEST_METHOD']  = $override;
            }
            //白名单之外的值一律忽略，保持原请求方法
        } elseif ($env['REQUEST_METHOD'] == "POST") {
            //form __METHOD
            $__method = $this->vitex->req->body[$this->overrideKey];
            if (is_string($__method) && $__method !== '') {
                $__method = strtoupper(trim($__method));
                if (in_array($__method, self::OVERRIDE_ALLOWED, true)) {
                    $env['ORIGINAL_METHOD'] = 'POST';
                    $env['REQUEST_METHOD']  = $__method;
                }
            }
        }
        $this->runNext();
    }
}
