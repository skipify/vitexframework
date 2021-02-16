<?php

/**
 * 过滤所有request中的空格
 */
namespace vitex\middleware;


use vitex\helper\Set;
use vitex\Middleware;

class TrimRequest extends Middleware
{
    /**
     * 调用中间件
     */
    public function call()
    {
        $isTrim = $this->vitex->getConfig('request.trim');
        if($isTrim){
            $this->trim();
        }
        $this->runNext();
    }

    private function trim(){
        foreach($this->vitex->req->query as $key=>$query){
            if(is_string($query)){
                $this->vitex->req->query[$key] = trim($query);
            }
        }
        foreach($this->vitex->req->body as $key=>$body){
            if(is_string($body)){
                $this->vitex->req->body[$key] = trim($body);
            }
        }
    }
}