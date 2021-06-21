<?php


namespace vitex\helper\attribute\parsedata;


use vitex\helper\attribute\parser\ParseDataBase;
use vitex\helper\attribute\parser\ParseDataInterface;

/**
 * 解析模型的数据
 * @package vitex\helper\attribute\parsedata
 */
class ModelData extends ParseDataBase implements ParseDataInterface
{
    private string $tableName;

    private string $primaryKey;

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