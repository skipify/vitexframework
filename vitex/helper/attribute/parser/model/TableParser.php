<?php

namespace vitex\helper\attribute\parser\model;


use vitex\core\attribute\model\Table;
use vitex\helper\attribute\AttributeTemporaryStore;
use vitex\helper\attribute\parsedata\ModelData;
use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;

class TableParser extends AttributeParserBase implements AttributeParserInterface
{
    private ModelData $data;

    public function parse(\ReflectionAttribute $attribute, $instance = null, $reflectInstance = null)
    {
        $modelData = new ModelData();
        $modelData->setParse($this);

        /**
         * @var $instance Table
         */
        $instance = $instance ? $instance : $attribute->newInstance();

        $modelData->setTableName($instance->getTableName());
        $modelData->setPrimaryKey($instance->getPrimaryKey());

        $this->data = $modelData;
        return $this->data;
    }

    public function doFinal(array $attributes)
    {
        $data = [
            'className' => $this->data->getAttributedClassName(),
            'tableName' => $this->data->getTableName(),
            'primaryKey' => $this->data->getPrimaryKey()
        ];
        /**
         * 数据存储一下加载的时候用
         */
        AttributeTemporaryStore::instance()->add(AttributeTemporaryStore::TABLE, [$data['className'] => $data]);
    }

    /**
     * 初始化类需要从加载的地方来实现，不能直接一次全部加载，否则会造成大量无用对象产生
     * 此处需要在加载的时候产生
     */
    public function getInstance()
    {

    }

}