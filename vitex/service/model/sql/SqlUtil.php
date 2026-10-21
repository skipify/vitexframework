<?php


namespace vitex\service\model\sql;

/**
 * 处理一些SQL的数据格式
 * @package vitex\service\model\sql
 */
class SqlUtil
{

    /**
     * 严格校验并包裹列名(白名单)
     * 仅允许 字母/数字/下划线/中划线 以及点号分隔的 table.column 形式，
     * 禁止空格、制表符、括号、反引号等一切其他字符
     * 适用于where条件列、orderBy、groupBy、having键、insert/update字段名等纯列名场景
     * 需要表达式请使用 whereRaw 等显式原生方法
     * @param string $column 列名
     * @return string 包裹后的列名
     * @throws \InvalidArgumentException
     */
    public static function wrapStrictColumn(string $column): string
    {
        $column = trim($column);
        if (!preg_match('/^[A-Za-z0-9_\-]+(\.[A-Za-z0-9_\-]+)*$/', $column)) {
            throw new \InvalidArgumentException('不安全的列名(仅允许字母数字下划线中划线和点):' . $column);
        }
        return self::wrapColumn($column);
    }

    /**
     * 校验透传(不包裹反引号)的标识符/表达式片段的安全性
     * 透传片段会直接进入SQL，禁止注释符、语句分隔符、select等子查询关键字
     * 以及sleep等时间盲注函数；同时禁止 反引号+任意空白 的组合(防止闭合反引号后拼接额外条件)
     * 需要子查询的场景请使用 Model::sub() 对象方式传递
     * @param string $identifier 标识符或表达式
     * @return string 校验通过的标识符
     * @throws \InvalidArgumentException
     */
    public static function guardIdentifier(string $identifier): string
    {
        if (preg_match(self::IDENT_DANGER_PATTERN, $identifier)
            || (str_contains($identifier, '`') && preg_match('/\s/', $identifier))
        ) {
            throw new \InvalidArgumentException('不安全的标识符:' . $identifier);
        }
        return $identifier;
    }

    /**
     * 危险模式的组合式构建块(统一维护，避免多处正则失同步)
     */
    /** 注释符与语句分隔符 */
    private const RE_TERMINATORS = '--|\/\*|;|#';
    /** 时间盲注/文件读写/信息泄露函数(词形式，反引号内同样命中) */
    private const RE_DANGER_FUNCS = '\bsleep\b|\bbenchmark\b|\bextractvalue\b|\bupdatexml\b|\bload_file\b|\boutfile\b|\bdumpfile\b|\bdatabase\b|\bversion\b|\bcurrent_user\b|\bsession_user\b|\bsystem_user\b|\bconnection_id\b|\blast_insert_id\b';
    /** 子查询关键字(原生片段严格模式/标识符场景使用) */
    private const RE_SUBQUERY = '\bselect\b|\bunion\b|\bdelete\b|\bupdate\b|\binsert\b|\bdrop\b';
    /** 盲注函数族(标识符/表达式场景拦截，合法场景如ifnull/nullif经词边界不受影响) */
    private const RE_BLIND_FUNCS = '\bif\b|\bcase\b|\bascii\b|\bord\b|\bsubstr\b|\bsubstring\b|\bmid\b|\bchar\b|\belt\b';

    /** 原生SQL片段危险模式(注释/分隔符/危险函数，select等由严格模式追加) */
    private const RAW_DANGER_PATTERN = '/(' . self::RE_TERMINATORS . '|' . self::RE_DANGER_FUNCS . ')/i';
    /** 标识符/表达式危险模式(追加子查询关键字与盲注函数族) */
    private const IDENT_DANGER_PATTERN = '/(' . self::RE_TERMINATORS . '|' . self::RE_DANGER_FUNCS . '|' . self::RE_SUBQUERY . '|' . self::RE_BLIND_FUNCS . ')/i';

    /**
     * 校验原生SQL片段的安全性(whereRaw/union/query/execute等入口)
     * 恒定拦截: 注释符(-- /* #)、语句分隔符(;)、时间盲注/文件/信息泄露函数
     * allowSelect=false 时额外拦截 select/union/delete/update/insert/drop (如setRaw的表达式无需子查询)
     * 完整语句/子查询场景(select合法)请传 true
     * @param string $sql SQL片段
     * @param bool $allowSelect 是否允许select/union等子查询关键字
     * @return string 校验通过的SQL片段
     * @throws \InvalidArgumentException
     */
    public static function guardSqlFragment(string $sql, bool $allowSelect = true): string
    {
        if (preg_match(self::RAW_DANGER_PATTERN, $sql)) {
            throw new \InvalidArgumentException('不安全的SQL片段:' . $sql);
        }
        if (!$allowSelect && preg_match('/(' . self::RE_SUBQUERY . ')/i', $sql)) {
            throw new \InvalidArgumentException('不安全的SQL片段(不允许子查询关键字):' . $sql);
        }
        return $sql;
    }

    /**
     * 校验构建期的标识符(已经wrapColumn/wrapStrictColumn处理过的存储形式)
     * 完整反引号包裹的形式(含 点号/别名/星号)是安全的字面标识符直接放行，
     * 其余(函数表达式/透传片段)按guardIdentifier黑名单校验
     * 用于构建期防线：即使_sql被setSqlSet等方式注入了未校验的数据也会在构建时拦截
     * @param string $id 已格式化的标识符
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function guardBuiltIdentifier(string $id): string
    {
        if ($id === '*' || preg_match('/^`[^`]+`(\.`[^`]+`)?(\.[*])?( as `[^`]+`)?$/', $id)) {
            return $id;
        }
        //括号配平的函数/聚合表达式(如 max(`id`) as info)按完整黑名单校验(含if/case/ascii等盲注函数族)
        if (str_contains($id, '(') && substr_count($id, '(') === substr_count($id, ')')) {
            return self::guardExpression($id);
        }
        return self::guardIdentifier($id);
    }

    /**
     * 校验函数/聚合表达式：按guardIdentifier同款完整黑名单校验，
     * 但不检查 反引号+空白 规则(合法聚合表达式如 max(`id`) as info 含该形式)
     * @param string $id 表达式
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function guardExpression(string $id): string
    {
        if (preg_match(self::IDENT_DANGER_PATTERN, $id)) {
            throw new \InvalidArgumentException('不安全的表达式:' . $id);
        }
        return $id;
    }

    /**
     * 校验表名(如from/join的表，可能携带子查询)的安全性
     * 表名参数允许携带select子查询(文档化用法)，拦截注释符、语句分隔符与时间/文件/信息泄露函数
     * @param string $table 表名或子查询
     * @return string 校验通过的表名
     * @throws \InvalidArgumentException
     */
    public static function guardTableIdentifier(string $table): string
    {
        if (preg_match(self::RAW_DANGER_PATTERN, $table)) {
            throw new \InvalidArgumentException('不安全的表名:' . $table);
        }
        return $table;
    }

    /**
     * 格式化表名，子查询等复杂表名透传(仅做轻量安全校验)，普通表名走列名级包裹
     * @param string $table 表名
     * @return string
     */
    public static function wrapTable(string $table): string
    {
        $table = trim($table);
        if (!$table) {
            return $table;
        }
        //子查询 (select ...) as t 等复杂表名
        if (str_contains($table, '(') || str_contains($table, ')')) {
            return self::guardTableIdentifier($table);
        }
        return self::wrapSelectColumn($table);
    }

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
            return self::guardIdentifier($column);
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
            return self::guardIdentifier($column);
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