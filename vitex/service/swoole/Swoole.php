<?php


namespace vitex\service\swoole;



use Swoole\Http\Request;
use vitex\Vitex;

class Swoole
{

    private Request | \Swoole\Http2\Request $request;

    private $response;

    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    public function setResponse($response)
    {
        $this->response = new $response;
        return $this;
    }

    public function request(Vitex $vitex)
    {

        $_SERVER['PATH_INFO'] = $this->request->server['path_info'];
        $_SERVER['REQUEST_METHOD'] = $this->request->server['request_method'];
        $vitex->env->method($_SERVER['REQUEST_METHOD']);
        $vitex->env->setPathinfo($_SERVER['PATH_INFO']);

        //设置header
        foreach ($this->request->header as $key=>$val){
            $vitex->env->set($key,$val);
        }
        //server
        foreach ($this->request->server as $key=>$val){
            $vitex->env->set($key,$val);
        }

        $_GET = $this->request->get;
        $_POST = $this->request->post;
        $_FILES = $this->request->files;
        $_COOKIE = $this->request->cookie;
        /**
         * 重新初始化当前请求的数据
         */
        $vitex->req->initData();
        return $vitex;
    }
}