<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package Vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace Vitex\Middleware;

/**
 * 把相关的Session信息附加到 req对象中
 */
class Session extends \Vitex\Middleware implements \ArrayAccess, \Iterator, \Countable
{
    use \Vitex\helper\SetMethod;
    public function __construct($sid = '')
    {
        if (session_id() == '') {
            if ($sid) {
                session_id($sid);
            }
            session_start();
        }
    }

    public function call()
    {
        $this->vitex->req['session'] = $this;
        $this->runNext();
    }

    public function offsetExists($val)
    {
        return isset($_SESSION[$val]);
    }

    public function offsetSet($key, $val)
    {
        if (is_null($key)) {
            $_SESSION[] = $val;
        } else {
            $_SESSION[$key] = $val;
        }
    }

    public function offsetGet($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public function offsetUnset($key)
    {
        unset($_SESSION[$key]);
    }

    //Iterator methods
    //
    public function rewind()
    {
        reset($_SESSION);
    }

    public function key()
    {
        return key($_SESSION);
    }

    public function next()
    {
        return next($_SESSION);
    }

    public function current()
    {
        return current($_SESSION);
    }

    public function count()
    {
        return count($_SESSION);
    }
}
