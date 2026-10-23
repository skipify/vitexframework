<?php
/**
 * 使用cookie实现session
 */

namespace vitex\middleware;


use vitex\helper\SetMethod;
use vitex\helper\traits\SetTrait;
use vitex\helper\Utils;
use vitex\Middleware;
use vitex\service\config\CookieConfig;
use vitex\service\session\SessionHandler;
use vitex\Vitex;

class CookieSession extends Middleware implements \ArrayAccess, \Iterator, \Countable
{
    use SetTrait;

    private $cookieData;

    public function __construct($sid = '')
    {
    }


    public function call()
    {
        $this->getCookie();
        $this->vitex->req->session = $this;
        $this->runNext();
    }

    /**
     * 获取cookie加密密钥：优先cookie配置组，回退旧版cookies.secret_key顶层配置，
     * 禁止空值与框架公开的默认密钥(fail-closed)，防止离线伪造会话cookie
     * @return string
     */
    private function getSecretKey(): string
    {
        $secret_key = '';
        $config = $this->vitex->getConfig('cookie');
        if (is_array($config) && $config) {
            $secret_key = CookieConfig::fromArray($config)->getSecretKey();
        }
        if ($secret_key === '') {
            //兼容旧版顶层配置
            $secret_key = (string)$this->vitex->getConfig('cookies.secret_key');
        }
        CookieConfig::assertSecretKey($secret_key);
        return $secret_key;
    }

    /**
     * 设置cookie
     * @param array $value
     * @throws \vitex\core\Exception
     */
    private function saveCookie(array $value)
    {
        $secret_key = $this->getSecretKey();
        $this->cookieData = array_merge($this->cookieData,$value);
        $this->vitex->req->session = $this;
        $value = Utils::encryptSafe(json_encode($this->cookieData,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $secret_key);
        //会话cookie生命周期与会话配置一致(分钟)；secure默认关闭以兼容纯HTTP部署，可通过cookie配置开启；httponly防脚本读取；SameSite=Lax缓解CSRF
        $sessionLifetime = (int)(((array)$this->vitex->getConfig('session'))['lifetime'] ?? 0);
        $cookieConfig = CookieConfig::fromArray((array)$this->vitex->getConfig('cookie'));
        setcookie('v', $value, [
            'expires'  => VITEX_NOW + ($sessionLifetime > 0 ? $sessionLifetime : 15) * 60,
            'path'     => $cookieConfig->getPath() ?: '/',
            'domain'   => $cookieConfig->getDomain(),
            'secure'   => $cookieConfig->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * 获取cookie
     * 解密失败(旧格式/被篡改的cookie)时按无会话数据处理，避免抛出异常导致500
     */
    private function getCookie()
    {
        $secret_key = $this->getSecretKey();
        $val = isset($_COOKIE['v']) ? $_COOKIE['v'] : '';
        try {
            $decryptVal = $val ? Utils::decryptSafe($val,$secret_key):'';
        } catch (\Exception $e) {
            $decryptVal = '';
        }
        $this->cookieData = $decryptVal ? json_decode($decryptVal,true):[];
    }


    /**
     * 设置session的值
     * @param  mixed $key session键名，如果为数组时则为包含键值的一个关联数组
     * @param  mixed $val session值，如果第一个参数是数组的时候此参数不需要指定
     * @return $this
     */
    public function set($key, $val = null)
    {
        if (is_array($key)) {
            $this->saveCookie($key);
        } else {
            $this->saveCookie([$key=>$val]);
        }
        return $this;
    }

    /**
     * 获取指定键名的session值，如果不指定则返回整个session
     * @param  mixed $key 键名
     * @return mixed 返回的值
     */
    public function get($key = null)
    {
        if ($key) {
            return $this->offsetGet($key);
        } else {
            return $this->cookieData;
        }
    }

    public function offsetExists($val): bool
    {
        return isset($this->cookieData[$val]);
    }

    public function offsetSet($key, $val): void
    {
        if (is_null($key)) {
            $this->set($key);
        } else {
            $this->set($key,$val);
        }
    }

    public function offsetGet($key): mixed
    {
        return $this->cookieData[$key] ?? null;
    }

    public function offsetUnset($key): void
    {
        unset($this->cookieData[$key]);
        $this->saveCookie($this->cookieData);
    }

    //Iterator methods
    //
    public function rewind(): void
    {
        reset($this->cookieData);
    }

    public function key(): mixed
    {
        return key($this->cookieData);
    }

    public function next(): void
    {
        next($this->cookieData);
    }

    public function current(): mixed
    {
        return current($this->cookieData);
    }

    public function count(): int
    {
        return count($this->cookieData);
    }
}