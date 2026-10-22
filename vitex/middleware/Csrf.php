<?php declare(strict_types=1);
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
     * 生成本次请求token之前会话中已存在的token快照
     * 非空说明token来自跨请求持久化的会话，verify时以会话token为唯一真值源
     * @var string|null
     */
    private $sessionTokenSnapshot = null;

    /**
     * 执行中间件，使之生效
     * @return [type] [description]
     */
    public function call()
    {
        if($this->vitex->getConfig('csrf.open')){
            //在getCsrfHtml生成本次token之前快照会话中的token，
            //若快照非空则说明会话跨请求持久化，verify以该值为唯一真值
            $this->sessionTokenSnapshot = $this->vitex->req->session['_csrf_token'] ?? null;
            $this->getCsrfHtml();
            $this->verify();
        }
        $this->runNext();
    }

    /**
     * 验证csrf_token信息是否正确
     * 覆盖所有会变更状态的方法(POST/PUT/PATCH/DELETE)，
     * 防止通过MethodOverride等方式把请求改写为DELETE/PATCH绕过验证
     * @return bool
     * @throws TokenMismatchException
     */
    public function verify()
    {
        $method = strtoupper($this->vitex->env->method());
        if(in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $this->vitex->req->body->_token;
            if(!$token){
                $token = $this->vitex->env->get('HTTP_X_CSRF_TOKEN');
            }
            $cookieToken = $this->vitex->req->cookies->csrf_token;
            $sessionToken = $this->sessionTokenSnapshot;
            if(is_string($sessionToken) && $sessionToken !== ''){
                /*
                 * 会话token跨请求持久化：以服务端会话为唯一真值源
                 * 提交的token必须为非空字符串且与会话token严格一致(hash_equals防时序攻击)
                 * 修复：双空(null==null)绕过、宽松!=比较、以及仅比对客户端cookie
                 * 导致的子域cookie注入(cookie tossing)绕过
                 */
                $valid = is_string($token) && $token !== '' && hash_equals($sessionToken, $token);
            } else {
                /*
                 * 无持久会话时退化为double-submit校验：
                 * token与cookie必须均为非空字符串且严格相等(hash_equals防时序攻击)，
                 * 修复：双方均缺失时(null==null)被判定相等而放行的绕过
                 */
                $valid = is_string($token) && $token !== ''
                    && is_string($cookieToken) && $cookieToken !== ''
                    && hash_equals($cookieToken, $token);
            }
            if(!$valid){
                $pathinfo = $this->vitex->env->getPathinfo();
                $except = $this->vitex->getConfig('csrf.except') ?: [];
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
        if(!isset($this->vitex->req->session['_csrf_token'])){
            //使用密码学安全的随机源生成token(md5(rand().time())可预测)
            $token = bin2hex(random_bytes(16));
            $this->vitex->req->session['_csrf_token'] = $token;
        } else {
            $token = $this->vitex->req->session['_csrf_token'];
        }
        $this->saveScrefToken($token);
        $this->vitex->emit("sys.after.generate_csrf_token",[$token]);
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