<?php

/**
 * Vitex 一个基于php8.0开发的 快速开发restful API的微型框架
 * @version  2.0.0
 *
 * @package vitex\service\model
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex\service\model;

use vitex\core\attribute\model\Association;
use vitex\core\attribute\model\Collection;
use vitex\core\attribute\model\NotTableField;
use vitex\core\attribute\model\TableField;
use vitex\helper\attribute\exception\NotFoundClassException;
use vitex\helper\attribute\parsedata\CollectionData;
use vitex\helper\attribute\parsedata\TableFieldData;
use vitex\Vitex;

/**
 * 属性
 * @package vitex\service\model
 */
class Property
{
    private \ReflectionClass $reflect;

    private string $className;

    public function __construct($entityClass)
    {
        if (!class_exists($entityClass)) {
            throw new NotFoundClassException("Can't found class " . $entityClass);
        }
        $this->reflect = new \ReflectionClass($entityClass);
        $this->className = $entityClass;
    }

    private function getPropertyName()
    {
        $properties = $this->reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $names = [];
        $_properties = [];
        /**
         * @var $property \ReflectionProperty
         */
        foreach ($properties as $property) {
            $names[] = $property->getName();
            $_properties[$property->getName()] = $property;
        }
        return [$names, $_properties];
    }

    /**
     * 1. 过滤 nottablefield注解
     * 2. 应用TableField注解 设置好对应关系
     */
    public function filter()
    {
        static $entityProperty = [];
        if (isset($entityProperty[$this->className])) {
            return $entityProperty[$this->className];
        }
        $vitex = Vitex::getInstance();
        $namePear = [];
        list($names, $fieldReflect) = $this->getPropertyName();
        $attributes = $vitex->attributes[NotTableField::class] ?? [];
        $propertyAttributes = $attributes[$this->className]['property'] ?? [];
        /**
         * 键值对  属性名=》字段名
         */
        foreach ($names as $name) {
            if (!isset($propertyAttributes[$name])) {
                $namePear[$name] = Utils::snakeCase($name);
            }
        }
        if (isset($vitex->attributes[TableField::class][$this->className]['property'])) {

            $fieldAttributes = $vitex->attributes[TableField::class][$this->className]['property'];

            /**
             * @var $fieldAttribute TableFieldData
             */
            foreach ($fieldAttributes as $fieldAttribute) {
                //数据库字段名
                $alias = $fieldAttribute->getAlias();
                if ($alias) {
                    $namePear[$fieldAttribute->getTarget()->getName()] = $alias;
                }
            }
        }

        $associationData = [];
        if (isset($vitex->attributes[Association::class][$this->className]['property'])) {
            $fieldAttributes = $vitex->attributes[Association::class][$this->className]['property'];

            /**
             * @var $fieldAttribute CollectionData
             */
            foreach ($fieldAttributes as $fieldAttribute) {
                //数据库字段名
                $fieldMap = $fieldAttribute->getFieldMap();
                /**
                 * 参数类型
                 */
                $propertyType = $fieldAttribute->getTarget()->getType()->getName();
                $associationData[$fieldAttribute->getAttributedPropertyName()] = [$propertyType,$fieldMap];
            }
        }

        $collectionData = [];
        if (isset($vitex->attributes[Collection::class][$this->className]['property'])) {
            $fieldAttributes = $vitex->attributes[Collection::class][$this->className]['property'];

            /**
             * @var $fieldAttribute CollectionData
             */
            foreach ($fieldAttributes as $fieldAttribute) {
                //数据库字段名
                $fieldMap = $fieldAttribute->getFieldMap();
                $collectionData[$fieldAttribute->getAttributedPropertyName()] = [$fieldAttribute->getPropertyTypeName(),$fieldMap];
            }
        }

        /**
         * 属性=》字段
         * 字段反射列表
         * 关联字段列表
         * 集合字段列表
         */
        $entityProperty[$this->className] = [$namePear, $fieldReflect, $associationData, $collectionData];
        return $entityProperty[$this->className];
    }
}