<?php

namespace vitex\service\amqp\processor;

/**
 * 消费消息的接口
 * @package vitex\service\amqp\processor
 */
interface ProcessInterface
{
    /**
     * 处理消息的方法
     * @return mixed
     */
    public function process(\AMQPEnvelope $message, \AMQPQueue $q);
}