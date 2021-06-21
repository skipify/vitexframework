<?php


namespace vitex\helper\traits;


use Psr\Http\Message\UriInterface;

trait RequestTrait
{
    use MessageTrait;
    /**
     * @var UriInterface
     */
    public $uri;

    /**
     * @var string|null
     */
    public $requestTarget;

    public $method;

    /**
     *
     */
    public function factory()
    {

    }

    public function getRequestTarget()
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }
        $path = $this->uri->getPath();
        if ($path == '') {
            return '/';
        }

        $query = $this->uri->getQuery();
        if ($query) {
            $path = $path . '?' . $query;
        }
        return $path;
    }

    public function withRequestTarget($requestTarget)
    {
        if(preg_match("|\s|",$requestTarget)){
            throw  new \InvalidArgumentException('请求不目标不允许存在空格');
        }
        $newInstance = clone $this;
        $newInstance->requestTarget = $requestTarget;
        return $newInstance;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        if(!is_string($method) || $method == ''){
            throw  new \InvalidArgumentException("错误的请求方法");
        }
        $newInstance = clone $this;
        $newInstance->method = $method;
        return $newInstance;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $newInstance = clone $this;
        if($newInstance->uri == $uri){
            return $newInstance;
        }
        $newInstance->uri = $uri;

        if($preserveHost && !$this->hasHeader('host')){
            $newInstance->updateHost();
        }

        return $newInstance;
    }

    /**
     * 更新host信息
     * @return bool
     */
    private function updateHost()
    {
        $host = $this->uri->getHost();
        if($host == ''){
            return false;
        }
        $port = $this->uri->getPort();
        if($port !== null){
            $host .= ":" . $port;
        }

        if($this->hasHeader('host')){
            $headerKey = $this->headerKeys['host'];
        } else {
            $this->headerKeys['host'] = 'Host';
            $headerKey = 'Host';
        }
        //第一个元素是 host
        $this->headers = [$headerKey => [$host]] + $this->headers;
    }

}