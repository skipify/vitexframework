<?php


namespace vitex\helper\attribute\parsedata;

use vitex\helper\attribute\parser\ParseDataBase;
use vitex\helper\attribute\parser\ParseDataInterface;

/**
 * 数据实体表中一些数据关联查询的时候可能需要跨实体类
 * 此处需要存储一些实体类的对应关系
 * @package vitex\helper\attribute\parsedata
 */
class CollectionData extends ParseDataBase implements ParseDataInterface
{
    private array $fieldMap;

    private string $propertyTypeName;

    /**
     * @return array
     */
    public function getFieldMap(): array
    {
        return $this->fieldMap;
    }

    /**
     * @param array $fieldMap
     */
    public function setFieldMap(array $fieldMap): void
    {
        $this->fieldMap = $fieldMap;
    }

    /**
     * @return string
     */
    public function getPropertyTypeName(): string
    {
        return $this->propertyTypeName;
    }

    /**
     * @param string $propertyTypeName
     */
    public function setPropertyTypeName(string $propertyTypeName): void
    {
        $this->propertyTypeName = $propertyTypeName;
    }




}