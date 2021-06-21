<?php


namespace vitex\service\model\sql;

/**
 * 处理一些SQL的数据格式
 * @package vitex\service\model\sql
 */
class SqlUtil
{

    /**
     * 格式化表名，列名 使用 `` 包裹
     * @param $column
     * @return string
     */
    public static function wrapColumn(string $column)
    {
        $column = trim($column);
        //格式化一下column
        // 已经wrapper或者是一个系统函数
        if (str_contains($column, '`') || str_contains($column, '(') || str_contains($column, '*')) {
            return $column;
        }
        /**
         * 字段名比较复杂的
         * table.field
         * db.table.field
         */
        if (strpos($column, '.') !== false) {
            $columnSeg = explode('.', $column);
            if (count($columnSeg) == 2) {
                //表.字段
                list($table, $field) = $columnSeg;
                if ($field == '*') {
                    return sprintf('`%s`.*', $table);
                } else {
                    return sprintf('`%s`.`%s`', $table, $field);
                }
            } else {
                //库.表.字段
                list($db, $table, $field) = $columnSeg;
                if ($field == '*') {
                    return sprintf('`%s`.`%s`.*', $db, $table);
                } else {
                    return sprintf('`%s`.`%s`.`%s`', $db, $table, $field);
                }
            }
        } else {
            return sprintf('`%s`', $column);
        }
    }


    /**
     * Select查询的字段处理方式，会处理as的形式
     * @param $column
     * @return string
     */
    public static function wrapSelectColumn(string $column)
    {
        $column = trim($column);

        if (!$column) {
            return $column;
        }
        //格式化一下column
        if (str_contains($column, '`') || str_contains($column, '(') || str_contains($column, ')') || str_contains($column, '*')) {
            return $column;
        }

        //包含别名的形式
        //多个空白替换为 一个空格

        $column = preg_replace('/[ \t]+/', ' ', $column);
        if (str_contains(strtolower($column), ' as ') || str_contains($column, ' ')) {
            $_column = explode(' ', $column);
            if (count($_column) == 3) {
                //  column as alias 形式
                list($column, , $alias) = $_column;
            } else {
                // column  alias 形式
                list($column, $alias) = $_column;
            }
        }
        $formatColumn = self::wrapColumn($column);
        if (isset($alias)) {
            return $formatColumn . ' AS ' . self::wrapColumn($alias);
        } else {
            return $formatColumn;
        }
    }

    /**
     * 清理包裹的 反引号，只返回列名
     * table.column => column
     * db.table.column => column
     * @param $column
     * @return string
     */
    public static function cleanColumn(string $column): string
    {
        if (str_contains($column, '.')) {
            $column = substr($column, strrpos($column, '.') + 1);
        }
        return strtr($column, ['('=>'','`'=> '',')'=>'','+'=>'','-'=>'']);
    }

    /**
     * 给查询的数据添加上引号等，处理where in 等参数
     * @param string|array $val
     * @return string
     */
    public static function formatVal(string|array $val)
    {
        /**
         * 如果是数组的话需要处理为字符串
         */
        if (is_array($val)) {
            foreach ($val as $_k => $_v) {
                $val[$_k] = self::formatVal($_v);
            }
            $val = implode(',', $val);
        } else {
            if (str_contains($val, "'") || str_contains($val, '"')) {
                throw new \InvalidArgumentException($val . " 中包含了引号，系统无法判断其安全性，请使用raw方法自行处理");
            } else {
                $val = "'" . $val . "'";
            }
        }
        return $val;
    }

    /**
     * 判断是否是子查询，主要是用在 whereIn中，其他的暂时不加
     * @param mixed $query
     * @return bool
     */
    public static function isSubQuery(string $query)
    {
        if (preg_match("/select\s+(.?)+\s+from./msi", $query)) {
            return true;
        }
        return false;
    }
}