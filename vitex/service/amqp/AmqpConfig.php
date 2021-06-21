<?php


namespace vitex\service\amqp;


use vitex\service\config\ConfigInterface;

/**
 * amqp配置
 * @package vitex\service\amqp
 */
class AmqpConfig implements ConfigInterface
{

    private string $host = '';

    private string $vhost = '';

    private int $port = 15672;

    private string $user = '';

    private string $password = '';

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getVhost(): string
    {
        return $this->vhost;
    }

    /**
     * @param string $vhost
     */
    public function setVhost(string $vhost): void
    {
        $this->vhost = $vhost;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'login' => $this->user,
            'password' => $this->password,
            'port' => $this->port,
            'vhost' => $this->vhost
        ];
    }

    public static function fromArray(array $config): self
    {
        $instance = new self();
        foreach ($config as $key => $val) {
            if ($key == 'login') {
                $instance->user = $val;
            } else {
                $instance->{$key} = $val;
            }
        }
        return $instance;
    }
}