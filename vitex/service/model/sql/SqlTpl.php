<?php


namespace vitex\service\model\sql;

/**
 * 拼接SQL的模板
 * @package vitex\service\model\sql
 */
class SqlTpl
{
    // delete from table where
    const DELETE = "DELETE FROM %s %s";

    //truncate table tablename
    const TRUNCATE = "TRUNCATE TABLE %s";

    //update table set setData  where
    const UPDATE = "UPDATE %s SET %s %s";

    /**
     * INSERT INTO tablenem (column) values (value)
     */
    const INSERT = "INSERT INTO %s (%s) values (%s)";

    // select columns from table join where
    // 1 columns 2 table 3 join 4 where
    const SELECT = "SELECT %s FROM %s %s %s";

    private function __construct()
    {
    }
}