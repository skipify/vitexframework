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

use vitex\service\model\exception\FieldNotAllowNullException;
use vitex\service\model\exception\FieldNotMatchException;

/**
 * 数据类型  会对插入数据库的类型进行初步的检测
 * @package vitex\service\model
 */
class DataType
{
    //INTEGER, INT, SMALLINT, TINYINT, MEDIUMINT, BIGINT   FLOAT, DOUBLE   DECIMAL, NUMERIC

    /**
     * 类型
     */
    const TYPE = [
        'INT'=>'intval',
        'TINYINT'=>'intval',
        'SMALLINT'=>'intval',
        'MEDIUMINT'=>'intval',
        'BIGINT'=>'intval',
        'FLOAT'=>'floatval',
        'DOUBLE'=>'doubleval',
        'DECIMAL'=>'doubleval',

        'DATE'=>'date',
        'TIME'=>'time',
        'YEAR'=>'year',
        'DATETIME'=>'datetime',
        'TIMESTAMP'=>'timestamp',
        'CHAR'=>'text',
        'VARCHAR'=>'text',
        'TINYTEXT'=>'text',
        'TEXT'=>'text',
        'MEDIUMTEXT'=>'text',
        'LONGTEXT'=>'text',

        'GEOMETRY'=>'text',
        'POINT'=>'text',
        'LINESTRING'=>'text',
        'POLYGON'=>'text',
        'MULTIPOINT'=>'text',
        'MULTILINESTRING'=>'text',
        'MULTIPOLYGON'=>'text',
        'GEOMETRYCOLLECTION'=>'text'
    ];


    /**
     * null
     * @param $val
     * @param $null
     * @throws FieldNotAllowNullException
     */
    private function null($val, $null)
    {
        if (!$null && $val == null) {
            throw new FieldNotAllowNullException();
        }
    }

    public function intval($val, $null = false)
    {
        $this->null($val, $null);
        if (!preg_match('/^[0-9]+$/', $val)) {
            throw new FieldNotMatchException($val);
        }
        return intval($val);
    }

    public function floatval($val, $null = false)
    {
        $this->null($val, $null);
        if (!preg_match('/^[0-9\\.]+$/', $val)) {
            throw new FieldNotMatchException($val);
        }
        return floatval($val);
    }

    public function doubleval($val, $null = false)
    {
        $this->null($val, $null);
        if (!preg_match('/^[0-9\\.]+$/', $val)) {
            throw new FieldNotMatchException($val);
        }
        return floatval($val);
    }

    public function date(string $val, $null = false)
    {
        $this->null($val, $null);
        if (strtotime($val) === false) {
            throw new FieldNotMatchException($val);
        }
        return $val;
    }

    public function time(string $val, $null = false)
    {
        $this->null($val, $null);
        return $val;

    }

    public function year(string $val, $null = false)
    {
        $this->null($val, $null);
        if (!preg_match('/^[0-9]+$/', $val)) {
            throw new FieldNotMatchException($val);
        }
        return intval($val);

    }

    public function datetime(string $val, $null = false)
    {
        $this->null($val, $null);
        if (strtotime($val) === false) {
            throw new FieldNotMatchException($val);
        }
        return $val;
    }

    public function timestamp(string $val, $null = false)
    {
        $this->null($val, $null);
        if (strtotime($val) === false) {
            throw new FieldNotMatchException($val);
        }
        return $val;
    }

    public function text(string $val, $null = false)
    {
        $this->null($val, $null);
        return $val;
    }
}