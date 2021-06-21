<?php


namespace vitex\service\model;


use vitex\service\config\ConfigInterface;

class DatabaseConfig implements ConfigInterface
{

    private string $host = '127.0.0.1';

    private int $port = 3306;

    private string $database = '';

    private string $username = 'root';

    private string $password = 'root';

    private string $prefix = "";

    private string $charset = 'utf8';

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
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @param string $database
     */
    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
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

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @param string $charset
     */
    public function setCharset(string $charset): void
    {
        $this->charset = $charset;
    }

    /**
     * @return string[]
     *     private string $host;
     */
    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'username' => $this->username,
            'password' => $this->password,
            'database' => $this->database,
            'port' => $this->port,
            'prefix' => $this->prefix,
            'charset' => $this->charset,
        ];
    }

    public static function fromArray(array $config): self
    {
        $instance = new self();

        foreach ($config as $key => $val) {
            $instance->{$key} = $val;
        }
        return $instance;
    }
}