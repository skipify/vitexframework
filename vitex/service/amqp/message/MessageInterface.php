<?php
namespace vitex\service\amqp\message;

use vitex\service\amqp\body\BodyInterface;

/**
 * 消息接口
 * @package vitex\service\amqp\message
 */
interface MessageInterface
{

    /**
     * 获取消息体
     * @return string
     */
    public function getBody():BodyInterface;

    /**
     * @return string | null
     */
    public function getRoutingKey():string | null;

    /**
     * 设置路由建
     * @param string $key
     * @return $this
     */
    public function setRoutingKey(string $key) : self;

    /**
     * 获取所有属性
     * @return array
     */
    public function extractAttributes():array;
}