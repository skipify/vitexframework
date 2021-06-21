<?php


namespace vitex\service\model\sql;

/**
 * 操作类型枚举 用于字符串替换
 * @package vitex\service\model\sql
 */
class WrapperConst
{

    /**
     * 相等操作
     */
    const EQ = "%s = %s";
    //!=
    const NE = "%s != %s";

    //>=
    const GE = "%s >= %s";
    //>
    const GT = "%s > %s";
    //<
    const LT = "%s < %s";
    //<=
    const LE = "%s <= %s";


    const LIKE = "%s LIKE %s";
    const NOT_LIKE = "%s NOT LIKE %s";

    const BETWEEN = "%s BETWEEN %s AND %s";
    const NOT_BETWEEN = "%s NOT BETWEEN %s AND %s";

    const EXISTS = "EXISTS (%s)";
    const NOT_EXISTS = "NOT EXISTS (%s)";

    const IN = "%s IN (%s)";
    const NOT_IN = "%s NOT IN (%s)";

    /**
     * 子查询
     */
    const SUB_IN = "%s IN (:sub)";
    const SUB_NOT_IN = "%s NOT IN (:sub)";


    const IS_NULL = "%s is null";
    const NOT_NULL = "%s is not null";

    // find_in_set
    const FIND_IN_SET = "find_in_set(%s,%s)";
}