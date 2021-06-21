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

use vitex\service\model\exception\EmptyDataException;
use vitex\service\model\exception\NotFoundConfigException;
use vitex\helper\attribute\AttributeTemporaryStore;
use vitex\service\model\sql\SelectWrapper;
use vitex\service\model\sql\SqlTpl;

/**
 * ORM实体基类
 * @package vitex\service\model
 */
class Entity
{
    /**
     * 生成的数据表的描述信息
     * static::_fieldDescribe 来描述
     */
    protected const _fieldDescribe = [

    ];

    /**
     * 直接查询数据,快捷方式  直接通过主键ID获取一个值
     * @param $id
     * @return $this | null
     * @throws \vitex\helper\attribute\exception\NotFoundClassException
     */
    public final function get(mixed $id)
    {
        //
        $model = $this->_getModel(new Model());
        $info = $model->get($id);
        if (!$info) {
            return null;
        }
        return static::fromArray($info, $this);
    }

    /**
     * 根据给定的模型条件获取单条数据
     * @param $model
     * @return $this|null
     * @throws NotFoundConfigException
     * @throws \vitex\helper\attribute\exception\NotFoundClassException
     */
    public final function getByModel($model)
    {
        $model = $this->_getModel($model);

        $info = $model->get();
        if (!$info) {
            return null;
        }
        return static::fromArray($info, $this);
    }

    /**
     * 根据值获得所有的行  多行 返回一个数组
     * @param $model
     * @return $this|null
     * @throws NotFoundConfigException
     * @throws \vitex\helper\attribute\exception\NotFoundClassException
     */
    public final function getAllByModel($model)
    {
        $model = $this->_getModel($model);
        $infos = $model->getAll();
        if (!$infos) {
            return null;
        }
        return static::fromArray($infos, $this);
    }


    /**
     * 直接使用sql查询数据 类方法
     * @param string $sql sql语句，可以使用 :key的形式替换具体的值
     * @param array $data 数据参数，可以替换sql中的值 预处理
     * @return Entity
     * @throws \vitex\core\Exception
     * @throws \vitex\helper\attribute\exception\NotFoundClassException
     */
    public static final function getBySql(string $sql, array $data = [])
    {
        $model = new Model();
        $sth = $model->getConnect(Model::SLAVER)->query($sql, $data);
        $infos = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return static::fromArray($infos);
    }

    /**
     * 使用预处理方式查询数据 实例方法
     * 在sql中可以使用 :key的形式进行占位
     * @param string $sql
     * @param array $data
     * @throws \vitex\core\Exception
     */
    public final function _getBySql(string $sql, array $data)
    {
        $model = new Model();
        $sth = $model->getConnect(Model::SLAVER)->query($sql, $data);
        $infos = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return static::fromArray($infos, $this);
    }

    /**
     * 常规查询  直接返回数组
     *
     * @param SelectWrapper $wrapper
     */
    public final function selectOne(SelectWrapper $wrapper)
    {
        $wrapper->limit(1);
        //1 columns 2 table 3 join 4 where
        $sql = sprintf(
            SqlTpl::SELECT,
            $wrapper->toSql(),
            $wrapper->getTable() ? $wrapper->getTable() : $this->getTableFromEntity(),
            '',
            $wrapper->build());
        $info = PdoUtil::instance()->getSlaver()->query($sql, $wrapper->dataHolder->getData())->fetch(\PDO::FETCH_ASSOC);
        return $info;
    }

    /**
     * 查询一个列表
     * 此查询如果是有 Collection注解的时候会忽略
     * @param SelectWrapper $wrapper
     * @return array
     * @throws NotFoundConfigException
     */
    public final function selectList(SelectWrapper $wrapper)
    {
        $sql = sprintf(
            SqlTpl::SELECT,
            $wrapper->toSql(),
            $wrapper->getTable() ? $wrapper->getTable() : $this->getTableFromEntity(),
            '',
            $wrapper->build()
        );
        $infos = PdoUtil::instance()->getSlaver()
            ->query($sql, $wrapper->dataHolder->getData())
            ->fetchAll(\PDO::FETCH_ASSOC);
        $data = [];
        foreach ($infos as $info) {
            $data[] = static::fromArray($info);
        }
        return $data;
    }


    /**
     * 保存当前数据
     * @return bool|int|mixed|null
     * @throws NotFoundConfigException
     * @throws \vitex\core\Exception
     * @throws \vitex\helper\attribute\exception\NotFoundClassException
     */
    public final function save()
    {
        $data = $this->toArray();
        if (!$data) {
            throw new EmptyDataException();
        }

        /**
         * 对于复杂类型过滤掉  不写入数据库
         * 例如数组、对象等
         */
        foreach ($data as $key => $val) {
            if (is_array($val) || is_object($val)) {
                unset($data[$key]);
            }
        }

        $model = $this->_getModel(new Model());
        $primaryKey = $model->getPk();

//        $fields = static::_fieldDescribe;
//
//        //验证插入数据
//        $dataType = new DataType();
//        foreach ($data as $key=>$val){
//            if($key == $primaryKey){
//                continue;
//            }
//
//            try {
//
//                if(!isset($fields[$key])){
//                    continue;
//                }
//                $validFunc = DataType::TYPE[$fields[$key]['dataType']];
//                $data[$key] = $dataType->{$validFunc}($val,DataType::TYPE[$fields[$key]['allowNull']]);
//            } catch (\Exception $e) {
//                continue;
//            }
//        }

        if (!empty($this->{$primaryKey})) {
            $model->where($primaryKey, '=', $this->{$primaryKey})->update($data);
        } else {
            $id = $model->insert($data);
            /**
             * 设置主键
             */
            $this->{$primaryKey} = $id;
        }
        return $id;
    }

    /**
     * 转为数组
     * @return array
     * @throws \vitex\helper\attribute\exception\NotFoundClassException
     */
    public final function toArray()
    {
        $property = new Property(get_class($this));
        list($fields) = $property->filter();
        $data = [];
        foreach ($fields as $property => $field) {
            if (isset($this->{$property})) {
                if (is_object($this->{$property})) {
                    $data[$field] = ($this->{$property})->toArray();
                } elseif (is_array($this->{$property})) {
                    $data[$field] = array_map(function ($item) {
                        if (is_object($item)) {
                            return $item->toArray();
                        } else {
                            return $item;
                        }
                    }, $this->{$property});
                } else {
                    $data[$field] = $this->{$property};
                }
            }
        }
        return $data;
    }

    /**
     * 从一个数组创建一个对象，
     * 如果传递了二维数组 也只会返回一个对象
     * 二维数组的话 当前实体字段会使用第一行数据
     * 如果二维数组时包含了集合注解的话会自动展开到集合中，否则则会丢弃数据
     * @param array $info
     * @param mixed $obj
     * @return Entity | array
     * @throws \vitex\helper\attribute\exception\NotFoundClassException
     */
    public final static function fromArray(array $info, $obj = null)
    {
        if (static::class == Entity::class) {
            return $info;
        }
        $data = $obj ? $obj : new static();
        $property = new Property(get_class($data));
        list($fields, $fieldReflect, $associationData, $collectionData) = $property->filter();
        /**
         * 是否是多维数组
         */
        $isMulti = false;
        $singleData = $info;
        if (count($info) != count($info, true)) {
            $isMulti = true;
            $singleData = $info[0];
        }
        foreach ($fields as $propertyName => $field) {
            if ($singleData[$field] === null && $fieldReflect[$propertyName]->getType()->allowsNull()) {
                $data->{$propertyName} = null;
            } elseif ($singleData[$field] !== null) {
                $data->{$propertyName} = $singleData[$field];
            }
        }
        unset($singleData);

        /**
         * 如果是 复杂类型的查询
         * 复杂类型指的是自定义的实体类 和 array集合
         */

        if ($isMulti) {
            /**
             * 二维数组
             */
            foreach ($collectionData as $property => list($propertyType, $fieldMap)) {
                if (!class_exists($propertyType)) {
                    throw new \InvalidArgumentException("Not Fount class " . $propertyType);
                }
                $arrayData = [];
                foreach ($info as $_info) {
                    $dataInstance = new $propertyType;

                    foreach ($fieldMap as $subProperty => $field) {
                        if (isset($_info[$field]) && $_info[$field] != null) {
                            $dataInstance->{$subProperty} = $_info[$field];
                        }
                    }
                    $arrayData[] = $dataInstance;
                }
                $data->{$property} = $arrayData;
            }
        } else {
            //一维数组
            foreach ($associationData as $property => list($propertyType, $fieldMap)) {
                if (!class_exists($propertyType)) {
                    throw new \InvalidArgumentException("Not Fount class " . $propertyType);
                }
                $dataInstance = new $propertyType;
                foreach ($fieldMap as $subProperty => $field) {
                    if (isset($info[$field]) && $info[$field] != null) {
                        $dataInstance->{$subProperty} = $info[$field];
                    }
                }
                $data->{$property} = $dataInstance;
            }
        }


        return $data;
    }

    /**
     * 获取一个模型
     * @param Model $model
     * @return Model
     * @throws NotFoundConfigException
     */
    private function _getModel(Model $model)
    {
        $dataSet = AttributeTemporaryStore::instance()->get(AttributeTemporaryStore::TABLE);
        $attributeConfig = $dataSet[get_class($this)];
        if (!$attributeConfig) {
            throw new NotFoundConfigException("Entity need Table Attribute");
        }
        if (!$model->getFromTable()) {
            $model->setTable($attributeConfig['tableName'] . ' t');
        }
        if ($attributeConfig['primaryKey']) {
            $model->setPk($attributeConfig['primaryKey']);
        }
        return $model;
    }

    /**
     * 从实体的注解中获取表名
     * @return mixed
     * @throws NotFoundConfigException
     */
    private function getTableFromEntity()
    {
        $dataSet = AttributeTemporaryStore::instance()->get(AttributeTemporaryStore::TABLE);
        $attributeConfig = $dataSet[get_class($this)];
        if (!$attributeConfig) {
            throw new NotFoundConfigException("Entity need Table Attribute");
        }
        return $attributeConfig['tableName'];
    }

    public function __toString(): string
    {
        return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}