<?php


namespace vitex\helper\attribute\parser\model;


use vitex\core\attribute\model\TableField;
use vitex\helper\attribute\parsedata\TableFieldData;
use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;

class TableFieldParser extends AttributeParserBase implements AttributeParserInterface
{
    private $data;
    public function parse(\ReflectionAttribute $attribute, $instance = null, \ReflectionParameter|\ReflectionMethod|\ReflectionClass|\ReflectionProperty $reflectInstance = null)
    {
        $tableData = new TableFieldData();

        /**
         * @var $instance TableField
         */
        $instance = $instance ? $instance : $attribute->newInstance();

        $tableData->setParse($this);
        $tableData->setAlias($instance->getAlias());

        $this->data = $tableData;
        return $tableData;
    }

    public function doFinal(array $attributes)
    {

    }

}