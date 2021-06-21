<?php


namespace vitex\helper\attribute\parser\model;


use vitex\core\attribute\model\Association;
use vitex\core\attribute\model\Collection;
use vitex\helper\attribute\parsedata\CollectionData;
use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;

/**
 * collection接口的解析器
 * @package vitex\helper\attribute\parser\model
 */
class CollectionParser extends AttributeParserBase implements AttributeParserInterface
{
    /**
     * @var CollectionData
     */
    private CollectionData $data;

    public function parse(\ReflectionAttribute $attribute, $instance = null, \ReflectionParameter|\ReflectionMethod|\ReflectionClass|\ReflectionProperty $reflectInstance = null)
    {
        /**
         * @var $instance Collection
         */
        $instance = $instance ? $instance : $attribute->newInstance();
        $collectionData = new CollectionData();
        $collectionData->setParse($this);
        $collectionData->setIsSlot(true);
        $collectionData->setFieldMap($instance->getFieldMap());
        $collectionData->setPropertyTypeName($instance->getClass());
        $this->data = $collectionData;
        return $this->data;
    }

    public function doFinal(array $attributes)
    {

    }

}