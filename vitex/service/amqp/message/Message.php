<?php


namespace vitex\service\amqp\message;

use vitex\service\amqp\body\BodyInterface;
use vitex\service\amqp\body\ContentType;
use vitex\service\amqp\body\Json;
use vitex\service\amqp\body\Text;

/**
 * 消息实体
 * @package vitex\service\amqp\message
 */
class Message implements MessageInterface
{
    private BodyInterface $body;

    private array $attributes;

    private array $headers;

    /**
     * $routingKey
     * @var string
     */
    private string | null $routingKey;

    public function __construct()
    {
        $this->initAttribute();
    }

    /**
     * 设置用户
     * @param string|array $content
     */
    public function setBodyContent(string|array $content)
    {
        if (is_array($content)) {
            $this->body = new Json($content);
        } else {
            $this->body = new Text($content);
        }
    }

    /**
     * 设置主体
     * @param BodyInterface $body
     */
    public function setBody(BodyInterface $body){
        $this->body = $body;
    }

    /**
     * 设置路由建
     * @param string | null $key
     * @return $this
     */
    public function setRoutingKey(string | null $key): self
    {
        $this->routingKey = $key;
        return $this;
    }

    /**
     * 获取路由Key
     * @return string | null
     */
    public function getRoutingKey(): string | null
    {
        return $this->routingKey;
    }

    /**
     * 设置请求头
     * @param string $key
     * @param $val
     * @return $this
     */
    public function setHeader(string $key, $val)
    {
        $this->headers[$key] = $val;
        return $this;
    }

    public function getHeader(string $key)
    {
        return $this->headers[$key] ?? null;
    }

    public function getBody(): BodyInterface
    {
        return $this->body;
    }

    /**
     * content_type        text/plain
     * content_encoding        NULL
     * message_id        NULL
     * user_id        NULL
     * app_id        NULL
     * delivery_mode        NULL
     * priority        NULL
     * timestamp        NULL
     * expiration        NULL
     * type        NULL
     * reply_to
     * 释放属性为一个数组
     * @return array
     */
    public function extractAttributes(): array
    {
        if (empty($this->attributes['timestamp'])) {
            $this->attributes['timestamp'] = VITEX_NOW;
        }
        $this->attributes['headers'] = $this->headers;
        $ret = $this->attributes;
        foreach ($ret as $key=>$val){
            if($val === null){
                unset($ret[$key]);
            }
        }
        return $ret;
    }

    /**
     * 设置属性
     * @param string $key
     * @param $value
     */
    public function setAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * 初始化attributes
     */
    private function initAttribute()
    {
        $this->attributes = array(
            'content_type' => ContentType::TEXT,
            'content_encoding' => 'utf8',
            'message_id' => uniqid(),
            'user_id' => null,
            'app_id' => null,
            'delivery_mode' => null,
            'priority' => null,
            'timestamp' => VITEX_NOW,
            'expiration' => null,
            'type' => null,
            'reply_to' => null,
            'correlation_id' => null,
            'headers' => [],
        );
    }

    public function __tostring()
    {
        return json_encode([
            'routing_key' => $this->routingKey,
            'body' => (string)$this->body,
            'attributes' => $this->attributes
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}