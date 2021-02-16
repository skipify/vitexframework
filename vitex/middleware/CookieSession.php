<?php
/**
 * 使用cookie实现session
 */

namespace vitex\middleware;


use vitex\helper\SetMethod;
use vitex\helper\Utils;
use vitex\Middleware;
use vitex\service\session\SessionHandler;
use vitex\Vitex;

class CookieSession extends Middleware implements \ArrayAccess, \Iterator, \Countable
{
    use SetMethod;

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
     * 设置cookie
     * @param array $value
     * @throws \vitex\core\Exception
     */
    private function saveCookie(array $value)
    {
        $secret_key = $this->vitex->getConfig('cookies.secret_key');
        $this->cookieData = array_merge($this->cookieData,$value);
        $this->vitex->req->session = $this;
        $value = Utils::encrypt(json_encode($this->cookieData,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $secret_key);
        setcookie('v', $value, time() + 15 * 60, '/', '', false, true);
    }

    /**
     * 获取cookie
     * @throws \vitex\core\Exception
     */
    private function getCookie()
    {
        $secret_key = $this->vitex->getConfig('cookies.secret_key');
        $val = isset($_COOKIE['v']) ? $_COOKIE['v'] : '';
        $decryptVal = $val ? Utils::decrypt($val,$secret_key):'';
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

    public function offsetExists($val)
    {
        return isset($this->cookieData[$val]);
    }

    public function offsetSet($key, $val)
    {
        if (is_null($key)) {
            $this->set($key);
        } else {
            $this->set($key,$val);
        }
    }

    public function offsetGet($key)
    {
        return $this->cookieData[$key] ?? null;
    }

    public function offsetUnset($key)
    {
        unset($this->cookieData[$key]);
        $this->saveCookie($this->cookieData);
    }

    //Iterator methods
    //
    public function rewind()
    {
        reset($this->cookieData);
    }

    public function key()
    {
        return key($this->cookieData);
    }

    public function next()
    {
        return next($this->cookieData);
    }

    public function current()
    {
        return current($this->cookieData);
    }

    public function count()
    {
        return count($this->cookieData);
    }
}