<?php


namespace vitex\core;

use vitex\service\Container;

/**
 * 系统上下文管理
 * 用于管理一次请求的上下文数据
 * @package vitex\core
 */
class Context
{
    private string $requestId = "";

    private ?Request $request = null;

    private ?Response $response = null;

    private ?Env $env = null;

    private ?Container $container = null;

    public function __construct()
    {

    }

    /**
     * 获取当前请求的Request
     */
    public function getRequest(): Request
    {
        if ($this->request) {
            return $this->request;
        }
        $this->request = new Request();
        $this->request->initData();
        return $this->request;
    }

    /**
     * 获取当前请求的Response
     */
    public function getResponse(): Response
    {
        if ($this->response) {
            return $this->response;
        }
        $this->response = new Response();
        return $this->response;
    }

    /**
     * 获取环境变量
     * @return Env
     */
    public function getEnv(): Env
    {
        if ($this->env) {
            return $this->env;
        }
        $this->env = new Env();
        return $this->env;
    }

    public function getContainer(): Container
    {
        if ($this->container) {
            return $this->container;
        }
        $this->container = new Container();
        return $this->container;
    }

    /**
     * 生成一个唯一的请求序列编号
     */
    public function getRequestId(): string
    {
        if ($this->requestId) {
            return $this->requestId;
        }
        $uniqueid = uniqid('', true);
        $this->requestId = substr($uniqueid, 0, 14) . '-' . substr($uniqueid, 15) . '-' . rand(10000000, 99999999);
        return $this->requestId;
    }

    /**
     * 克隆系统
     */
    public function __clone()
    {
        $this->requestId = "";
        $this->request = null;
        $this->response = null;
        $this->env = null;
        $this->container = null;
    }
}