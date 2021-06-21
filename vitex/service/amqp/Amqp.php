<?php

namespace vitex\service\amqp;


use vitex\service\amqp\message\MessageInterface;
use vitex\service\amqp\processor\ProcessInterface;

class Amqp
{
    //exchange  channel route

    /**
     * 定义一个简单的交换机，可以选择直接从rabbitmq管理界面提前创建好交换机
     * @param string $exchangeKey
     * @param string $flag
     * @return Exchange
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function createSimpleExchange(string $exchangeKey, $flag = AMQP_EX_TYPE_DIRECT): Exchange
    {
        $channel = AmqpUtil::instance()->defaultChannel();
        $exchange = new Exchange($channel);
        $exchange->setName($exchangeKey);
        $exchange->setType($flag);
        $exchange->declareExchange();
        return $exchange;
    }

    /**
     * 简单的信息发送  需要提前定义好 交换机
     * 如果消息为数组则会转为json字符串
     * @param $exchangeKey
     * @param MessageInterface $message
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     * @throws \AMQPQueueException
     */
    public function publishMessage($exchangeKey, MessageInterface $message)
    {
        $channel = AmqpUtil::instance()->defaultChannel();
        //创建交换机对象
        $exchange = new Exchange($channel);
        $exchange->setName($exchangeKey);
        //flags =>MQP_MANDATORY and AMQP_IMMEDIATE.
        $exchange->publish($message->getBody()->getContent(), $message->getRoutingKey(), AMQP_NOPARAM, $message->extractAttributes());
    }

    /**
     * 自定义发送
     * @param Exchange $exchange
     * @param MessageInterface $message
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function publish(Exchange $exchange, MessageInterface $message)
    {
        $exchange->publish($message->getBody()->getContent(), $message->getRoutingKey());
    }

    /**
     * 消费 简易的消费者
     * 默认交换机的默认队列
     *
     *
     * @param string $routingKey
     * @param $callback callable AMQPEnvelope $message, AMQPQueue $q 俩参数
     * @param $flag int 默认不自动确认  如果自动确认输入 AMQP_AUTOACK
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPEnvelopeException
     * @throws \AMQPQueueException
     */
    public function simpleConsume(string $routingKey, ProcessInterface $process, $flag = AMQP_NOPARAM)
    {
        //AMQP_AUTOACK
        $queue = AmqpUtil::instance()->defaultQueue($routingKey);
        $queue->consume([$process, 'process'], $flag);
    }

    /**
     * 自定义的消费消费队列
     * @param string $queueName
     * @param string $exchange 传递一个 exchangeKey
     * @param string $routingKey
     * @param $callback callable AMQPEnvelope $message, AMQPQueue $q 俩参数
     * @param $flag int 默认不自动确认  如果自动确认输入 AMQP_AUTOACK
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPEnvelopeException
     * @throws \AMQPQueueException
     */
    public function consume(string $queueName, string $exchange, string $routingKey, ProcessInterface $process, $flag = AMQP_NOPARAM)
    {
        //AMQP_AUTOACK
        $queue = new Queue(AmqpUtil::instance()->defaultChannel());
        $queue->setName($queueName);
        $queue->setFlags($flag);
        $queue->bind($exchange, $routingKey);
        $queue->consume([$process, 'process'], $flag);
    }

}