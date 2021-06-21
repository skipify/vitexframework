<?php declare(strict_types=1);


namespace vitex\core\attribute\model;

use vitex\core\attribute\AttributeInterface;
use vitex\core\attribute\sys\Slot;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\model\TableParser;

/**
 * 模型指定表名
 * @package vitex\core\attribute\model
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Table implements AttributeInterface
{
    /**
     * 默认的数据表主键
     */
    const PRIMARY_KEY = "id";


    public function __construct(
        private string $tableName,
        private string $primaryKey = self::PRIMARY_KEY
    ){
    }

    public function getParse(): AttributeParserInterface
    {
        return new TableParser();
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * @param string $primaryKey
     */
    public function setPrimaryKey(string $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }



}