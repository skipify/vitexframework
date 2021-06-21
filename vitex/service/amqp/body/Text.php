<?php

namespace vitex\service\amqp\body;

/**
 * Text类型
 * @package vitex\service\amqp\body
 */
class Text implements BodyInterface
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getContentType(): string
    {
        return ContentType::TEXT;
    }

    public function __tostring()
    {
        return (string)$this->content;
    }
}