<?php


namespace vitex\service\cache\config;


use vitex\service\config\ConfigInterface;

/**
 * Sqlite配置
 * @package vitex\service\cache\config
 */
class SqliteCacheConfig implements ConfigInterface
{

    private string $db = '';

    private string $table = '';

    /**
     * @return string
     */
    public function getDb(): string
    {
        return $this->db;
    }

    /**
     * @param string $db
     */
    public function setDb(string $db): void
    {
        $this->db = $db;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string $table
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    public function toArray(): array
    {
        return [
            'db' => $this->db,
            'table' => $this->table
        ];
    }

    public static function fromArray(array $config): self
    {
        $instance = new self();
        foreach ($config as $Key => $val) {
            $instance->{$key} = $val;
        }
        return $instance;
    }
}