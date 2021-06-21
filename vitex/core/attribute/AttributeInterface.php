<?php
namespace vitex\core\attribute;


use vitex\helper\attribute\parser\AttributeParserInterface;

interface AttributeInterface
{

    public function getParse():AttributeParserInterface;
}