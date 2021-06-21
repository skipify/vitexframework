<?php


namespace vitex\helper\attribute\parser\model;


use vitex\core\attribute\model\Association;
use vitex\core\attribute\model\TableField;
use vitex\helper\attribute\parsedata\CollectionData;
use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;

/**
 * AssociationParser解析器
 * @package vitex\helper\attribute\parser\model
 */
class AssociationParser extends AttributeParserBase implements AttributeParserInterface
{
    /**
     * @var CollectionData
     */
    private CollectionData $data;

    public function parse(\ReflectionAttribute $attribute, $instance = null, \ReflectionParameter|\ReflectionMethod|\ReflectionClass|\ReflectionProperty $reflectInstance = null)
    {
        /**
         * @var $instance Association
         */
        $instance = $instance ? $instance : $attribute->newInstance();
        $collectionData = new CollectionData();
        $collectionData->setParse($this);
        $collectionData->setIsSlot(true);
        $collectionData->setFieldMap($instance->getFieldMap());
        $this->data = $collectionData;
        return $this->data;
    }

    public function doFinal(array $attributes)
    {

    }

}