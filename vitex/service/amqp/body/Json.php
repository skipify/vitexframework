<?php


namespace vitex\service\amqp\body;

/**
 * json消息
 * @package vitex\service\amqp\body
 */
class Json implements BodyInterface
{
    private array $content;

    public function __construct(array $body)
    {
        $this->content = $body;
    }

    public function getContent()
    {
        return json_encode($this->content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function getContentType(): string
    {
        return ContentType::JSON;
    }

    public function __tostring()
    {
        return $this->getContent();
    }
}