<?php
/**
 * MessageInterface 实现
 */

namespace vitex\helper\traits;


use GuzzleHttp\Stream\Stream;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    public $protocolVersion;

    /**
     * 键值保持为设置时的大小写
     * @var array
     */
    public $headers = [];

    /**
     * 键值均转换为小写
     * @var array
     */
    private $headerKeys = [];

    private $stream;


    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        if ($this->protocolVersion == $version) {
            return $this;
        }
        $newInstance = clone $this;
        $newInstance->protocolVersion = $version;
        return $newInstance;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        $key = strtolower($name);
        return isset($this->_headers[$key]) ? true : false;
    }

    public function getHeader($name)
    {
        if ($this->hasHeader($name)) {
            $headerKey = $this->headerKeys[strtolower($name)];
            return $this->headers[$headerKey];
        }
        return [];
    }

    public function getHeaderLine($name)
    {
        return implode(',', $this->getHeader($name));
    }

    public function withHeader($name, $value)
    {
        $this->assertName($name);

        $newInstance = clone $this;
        $key = strtolower($name);
        //删除已经存储的内容
        if ($newInstance->hasHeader($name)) {
            unset($newInstance->headers[$newInstance->headerKeys[$key]]);
        }

        $newInstance->headers[$name] = $this->formatHeaderValue($value);
        $newInstance->headerKeys[$key] = $name;
        return $newInstance;
    }

    public function withAddedHeader($name, $value)
    {
        $this->assertName($name);

        $newInstance = clone $this;
        $key = strtolower($name);


        if ($newInstance->hasHeader($name)) {
            $headerKey = $this->headerKeys[$key];
            $newInstance->headers[$headerKey] = array_merge($newInstance->headers[$headerKey], $this->formatHeaderValue($value));
        } else {
            $newInstance->headers[$name] = $this->formatHeaderValue($value);
            $newInstance->headerKeys[$key] = $name;
        }
        return $newInstance;
    }

    public function withoutHeader($name)
    {
        $this->assertName($name);

        $key = strtolower($name);
        if (!isset($this->headerKeys[$key])) {
            return $this;
        }
        $newInstance = clone $this;


        unset($newInstance->headers[$this->headerKeys[$key]]);
        unset($newInstance->headerKeys[$key]);
        return $newInstance;
    }

    public function getBody()
    {
        return $this->stream ?? Stream::factory("");
    }

    public function withBody(StreamInterface $body)
    {
        if ($this->stream == $body) {
            return $this;
        }
        $newInstance = clone $this;
        $newInstance->stream = $body;
        return $newInstance;
    }

    /**
     * 判断键值是否合法
     * @param $name
     * @return bool
     */
    private function assertName($name)
    {
        if (!is_array($name) || $name == '') {
            throw new \InvalidArgumentException('不合法的键名');
        }
        return true;
    }

    /**
     * 格式化header数据
     * @param $value
     * @return array
     */
    private function formatHeaderValue($value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        if (count($value) == 0) {
            throw  new \InvalidArgumentException("不合法的Header值");
        }

        $value = array_map(function ($val) {
            if (!is_scalar($val) && $val !== null) {
                throw  new \InvalidArgumentException('不合法的Header值');
            }
            return trim($val, " \t");
        }, $value);

        return $value;
    }

}