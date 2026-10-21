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
     * 禁止 ||/&& 逻辑运算符(防止(1)||(1)式恒真条件拼接)；
     * 含函数调用时要求括号配平且函数在白名单内(防止hex()/conv()/get_lock()等任意函数造成外泄或盲注)
     * 需要子查询的场景请使用 Model::sub() 对象方式传递
     * @param string $identifier 标识符或表达式
     * @return string 校验通过的标识符
     * @throws \InvalidArgumentException
     */
    public static function guardIdentifier(string $identifier, bool $withBlind = true): string
    {
        if (preg_match($withBlind ? self::IDENT_DANGER_PATTERN : self::SELECT_DANGER_PATTERN, $identifier)
            || (str_contains($identifier, '`') && preg_match('/\s/', $identifier))
            || preg_match(self::RE_LOGIC_OPS, $identifier)
        ) {
            throw new \InvalidArgumentException('不安全的标识符:' . $identifier);
        }
        self::checkExpressionFunctions($identifier, '标识符');
        return $identifier;
    }

    /**
     * 校验进入条件位置(where/having/order/group)的透传列名/表达式
     * 在guardIdentifier基础上额外限制字符集: 禁止比较与逻辑运算符(=<>!&|^~)，
     * 比较运算必须经由操作符白名单参数传入，防止 (1)=(1) 式恒真条件拼接
     * @param string $column
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function guardConditionColumn(string $column): string
    {
        $column = self::guardIdentifier($column);
        if (!preg_match(self::COND_EXPR_PATTERN, $column)) {
            throw new \InvalidArgumentException('不安全的列名表达式(不允许比较/逻辑运算符):' . $column);
        }
        return $column;
    }

    /**
     * 校验透传表达式中的函数调用: 括号配平 + 函数白名单
     * @param string $id 表达式
     * @param string $label 报错场景描述
     * @throws \InvalidArgumentException
     */
    private static function checkExpressionFunctions(string $id, string $label): void
    {
        if (!str_contains($id, '(')) {
            return;
        }
        if (substr_count($id, '(') !== substr_count($id, ')')) {
            throw new \InvalidArgumentException('不安全的' . $label . '(括号不配平):' . $id);
        }
        if (preg_match_all('/([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $id, $matches)) {
            foreach ($matches[1] as $func) {
                if (!in_array(strtolower($func), self::ALLOWED_FUNCS, true)) {
                    throw new \InvalidArgumentException('不安全的' . $label . '(函数不在白名单):' . $id);
                }
            }
        }
    }

    /**
     * 危险模式的组合式构建块(统一维护，避免多处正则失同步)
     */
    /** 注释符与语句分隔符 */
    private const RE_TERMINATORS = '--|\/\*|;|#';
    /** 时间盲注/文件读写/信息泄露函数(词形式，反引号内同样命中) */
    private const RE_DANGER_FUNCS = '\bsleep\b|\bbenchmark\b|\bextractvalue\b|\bupdatexml\b|\bload_file\b|\boutfile\b|\bdumpfile\b|\bdatabase\b|\bversion\b|\bcurrent_user\b|\bsession_user\b|\bsystem_user\b|\bconnection_id\b|\blast_insert_id\b';
    /** 锁类函数(时间盲注原语，get_lock可替代sleep做延时)，应用SQL无合法用途 */
    private const RE_LOCK_FUNCS = '\bget_lock\b|\brelease_lock\b|\bis_free_lock\b|\bis_used_lock\b';
    /** 编码/数据外泄辅助函数(条件/字段透传场景无合法用途，可逐列导出数据) */
    private const RE_EXFIL_FUNCS = '\bhex\b|\bunhex\b|\bconv\b|\bbin\b|\baes_encrypt\b|\baes_decrypt\b|\bencode\b|\bdecode\b|\bcompress\b|\buncompress\b';
    /** 子查询关键字(原生片段严格模式/标识符场景使用) */
    private const RE_SUBQUERY = '\bselect\b|\bunion\b|\bdelete\b|\bupdate\b|\binsert\b|\bdrop\b';
    /** 盲注函数族(标识符/表达式场景拦截，合法场景如ifnull/nullif经词边界不受影响) */
    private const RE_BLIND_FUNCS = '\bif\b|\bcase\b|\bascii\b|\bord\b|\bsubstr\b|\bsubstring\b|\bmid\b|\bchar\b|\belt\b';
    /** 逻辑运算符拼接口(如(1)||(1)在MySQL中等价于OR，产生恒真条件) */
    private const RE_LOGIC_OPS = '/\|\||&&/';

    /**
     * 条件列(where/having/order/group)透传表达式允许的字符集:
     * 标识符/数字/四则与取模运算/引号字符串/括号/逗号/反引号/空白。
     * 比较与逻辑运算符(=<>!&|^~)必须经由操作符白名单参数传入，
     * 出现在列名表达式中即拒绝，防止 (1)=(1) 式恒真条件拼接
     */
    private const COND_EXPR_PATTERN = '/^[A-Za-z0-9_.*+\/%\- \t\'"(),`]+$/';

    /**
     * 透传表达式允许的函数白名单(聚合函数与常用标量函数)
     * 括号内的函数调用一律按此白名单校验，防止hex()/conv()/get_lock()/rand()等
     * 任意函数调用造成数据外泄或时间/布尔盲注
     */
    private const ALLOWED_FUNCS = [
        //聚合
        'count', 'sum', 'min', 'max', 'avg', 'group_concat', 'bit_and', 'bit_or', 'bit_xor', 'std', 'stddev', 'variance', 'var_pop', 'var_samp',
        //条件与空值处理
        'ifnull', 'nullif', 'coalesce', 'isnull',
        //字符串
        'concat', 'concat_ws', 'length', 'char_length', 'character_length', 'octet_length', 'bit_length',
        'trim', 'ltrim', 'rtrim', 'upper', 'ucase', 'lower', 'lcase', 'reverse', 'replace',
        'left', 'right', 'substring_index', 'lpad', 'rpad', 'repeat', 'space', 'instr', 'locate', 'position', 'field', 'format',
        //数值
        'abs', 'round', 'ceil', 'ceiling', 'floor', 'truncate', 'mod', 'greatest', 'least', 'sign', 'pow', 'power', 'sqrt', 'exp', 'ln', 'log', 'log2', 'log10',
        //日期时间
        'curdate', 'curtime', 'now', 'sysdate', 'unix_timestamp', 'from_unixtime', 'utc_timestamp', 'utc_date', 'utc_time',
        'date', 'time', 'year', 'month', 'day', 'dayname', 'dayofweek', 'dayofmonth', 'dayofyear', 'weekday', 'week', 'weekofyear', 'yearweek',
        'quarter', 'hour', 'minute', 'second', 'microsecond',
        'date_format', 'time_format', 'datediff', 'timediff', 'timestampdiff', 'timestampadd', 'date_add', 'date_sub', 'adddate', 'subdate',
        'last_day', 'from_days', 'to_days', 'sec_to_time', 'time_to_sec', 'makedate', 'maketime', 'str_to_date',
        //类型转换
        'cast', 'convert',
    ];

    /** 原生SQL片段危险模式(注释/分隔符/危险函数，select等由严格模式追加) */
    private const RAW_DANGER_PATTERN = '/(' . self::RE_TERMINATORS . '|' . self::RE_DANGER_FUNCS . ')/i';
    /** 标识符/表达式危险模式(追加子查询关键字与盲注函数族) */
    private const IDENT_DANGER_PATTERN = '/(' . self::RE_TERMINATORS . '|' . self::RE_DANGER_FUNCS . '|' . self::RE_SUBQUERY . '|' . self::RE_BLIND_FUNCS . ')/i';
    /** select字段危险模式(允许CASE WHEN等表达式，不含盲注函数族) */
    private const SELECT_DANGER_PATTERN = '/(' . self::RE_TERMINATORS . '|' . self::RE_DANGER_FUNCS . '|' . self::RE_SUBQUERY . ')/i';

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
        if (!$allowSelect && preg_match('/(' . self::RE_SUBQUERY . '|' . self::RE_BLIND_FUNCS . '|' . self::RE_LOCK_FUNCS . '|' . self::RE_EXFIL_FUNCS . ')/i', $sql)) {
            //严格模式(whereRaw/setRaw/having等条件片段)额外拦截:
            //盲注函数族(if/case/ascii/substr等)、锁函数(get_lock时间盲注)、编码外泄函数(hex/conv等)
            throw new \InvalidArgumentException('不安全的SQL片段(不允许子查询/盲注/锁/编码外泄函数):' . $sql);
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
     * 并追加逻辑运算符拦截与函数白名单校验(括号配平+白名单)，
     * 但不检查 反引号+空白 规则(合法聚合表达式如 max(`id`) as info 含该形式)
     * @param string $id 表达式
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function guardExpression(string $id): string
    {
        if (preg_match(self::IDENT_DANGER_PATTERN, $id) || preg_match(self::RE_LOGIC_OPS, $id)) {
            throw new \InvalidArgumentException('不安全的表达式:' . $id);
        }
        self::checkExpressionFunctions($id, '表达式');
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
     * 严格校验并包裹表名(白名单)
     * 仅允许 表名/库.表名，以及可选的单个别名： tbl / db.tbl / tbl t / db.tbl t
     * 字符集与wrapStrictColumn一致(字母/数字/下划线/中划线/点)，禁止空格、括号、反引号等一切其他字符
     * 不支持子查询等复杂表名，需要子查询请使用 Model::sub() 对象方式
     * @param string $table 表名
     * @return string 包裹后的表名
     * @throws \InvalidArgumentException
     */
    public static function wrapTableName(string $table): string
    {
        $table = trim($table);
        if (!$table) {
            throw new \InvalidArgumentException('表名不得为空');
        }
        if (!preg_match('/^([A-Za-z0-9_\-]+(?:\.[A-Za-z0-9_\-]+)*)(?:\s+([A-Za-z0-9_\-]+))?$/', $table, $m)) {
            throw new \InvalidArgumentException('不安全的表名(仅允许字母数字下划线中划线点以及可选别名):' . $table);
        }
        $name = self::wrapColumn($m[1]);
        return isset($m[2]) ? $name . ' ' . $m[2] : $name;
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
            //条件位置的透传表达式：函数白名单+括号配平+禁止比较/逻辑运算符(防恒真拼接)
            return self::guardConditionColumn($column);
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
            //select字段允许CASE WHEN等表达式，按不含盲注函数族的模式校验
            return self::guardIdentifier($column, false);
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
        //剥离占位符名不允许的字符(空格/运算符等)，保证生成的:占位符合法；为空时兜底
        $cleaned = preg_replace('/[^A-Za-z0-9_]/', '', $column);
        return $cleaned === '' ? 'col' : $cleaned;
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