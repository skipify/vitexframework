<?php
/**
 * 跨站防御
 * User: skipify
 * Date: 2017/8/22
 * Time: 下午3:21
 */

namespace vitex\middleware;


use vitex\core\Exception;
use vitex\Middleware;

class Csrf extends Middleware
{

    /**
     * 执行中间件，使之生效
     * @return [type] [description]
     */
    public function call()
    {
        if($this->vitex->getConfig('csrf.open')){
            $this->getCsrfHtml();
            $this->verify();
        }
        $this->runNext();
    }

    /**
     * 验证csrf_token信息是否正确
     * @return bool
     * @throws TokenMismatchException
     */
    public function verify()
    {
        if(strtoupper($this->vitex->env->method()) == 'POST' ||
            strtoupper($this->vitex->env->method()) == 'PUT'
        ) {
            $token = $this->vitex->req->body->_token;
            if(!$token){
                $token = $this->vitex->env->get('X-Csrf-Token');
            }
            $cookieToken = $this->vitex->req->cookies->csrf_token;
            $pathinfo = $this->vitex->env->getPathinfo();
            $except = $this->vitex->getConfig('csrf.except');
            if($token != $cookieToken){
                /*
                 * 一个符合排除的链接就排除
                 */
                foreach($except as $pattern){
                    if($this->vitex->route->router->checkUrlMatch($pattern,$pathinfo)){
                        return true;
                    }
                }

                //验证失败出错
                $callback = $this->vitex->getConfig('csrf.onmismatch');
                if($callback){
                    call_user_func_array($callback,[$token,$cookieToken]);
                    exit;
                } else {
                    throw new TokenMismatchException("CSRF TOKEN验证失败",Exception::CODE_PARAM_VALUE_ERROR);
                }
            }
        }
        return true;
    }

    /**
     * 返回一个token的html字符串
     * @return string
     */
    private function getCsrfHtml()
    {
        $html =  '<input type="hidden" name="_token" value="'.$this->getCsrfToken().'">';
        /**
         * 隐藏表单的html传递给前端
         */
        $this->vitex->res->set('csrf_token_html',$html);
        return $html;
    }

    /**
     * 获取一个token值
     * @return string
     */
    private function getCsrfToken()
    {
        $token = md5(rand(0,999).time());
        $this->saveScrefToken($token);
        $this->vitex->applyHook("sys.after.generate_csrf_token",$token);
        return $token;
    }

    /**
     * 默认保存csrftoken到cookie中
     * @param $token
     */
    private function saveScrefToken($token)
    {
        /**
         * 保存到cookie
         */
        $this->vitex->res->setCookie("csrf_token",$token);
        /**
         * 保存到http header中
         */
        $this->vitex->res->setHeader("X-Csrf-Token",$token);
        /**
         * 设置到 response中传递给模板变量
         */
        $this->vitex->res->set("csrf_token",$token);
    }
}

/**
 * csrf token 不匹配的异常
 * Class TokenMismatchException
 * @package vitex\middleware
 */
class TokenMismatchException extends Exception
{

}