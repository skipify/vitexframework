<?php


namespace vitex\service\amqp\body;

/**
 * 发送的消息体
 * @package vitex\service\amqp\body
 */
interface BodyInterface
{
    /**
     * 获得内容
     * @return mixed
     */
    public function getContent();

    /**
     * 返回类型
     * @return string
     */
    public function getContentType(): string;

    public function __tostring();
}