<?php


namespace vitex\helper\attribute\parser;

/**
 * 空解析
 * @package vitex\helper\attribute\parser
 */
class EmptyParser extends AttributeParserBase implements AttributeParserInterface
{

    public function parse(\ReflectionAttribute $attribute, $instance = null, \ReflectionParameter|\ReflectionMethod|\ReflectionClass|\ReflectionProperty $reflectInstance = null)
    {
        $data = new ParseDataBase();
        $data->setIsSlot(true);
        $data->setParse($this);
        return $data;
    }

    public function doFinal(array $attributes)
    {
    }
}