<?php


namespace vitex\service\amqp\body;

/**
 * 消息的 ContentType
 * @package vitex\service\amqp\body
 */

class ContentType
{
    const
        TEXT = 'text/plain',
        JSON = 'application/json',
        EMPTY_CONTENT = 'application/x-empty';
}