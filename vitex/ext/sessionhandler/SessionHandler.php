<?php
/**
 * 会话管理基类
 */

namespace vitex\ext\sessionhandler;


use vitex\Vitex;

class SessionHandler
{
    protected $vitex;
    public function __construct()
    {
        $this->vitex = Vitex::getInstance();
    }
}