<?php declare(strict_types=1);
/**
 * Vitex 一个基于php7.0开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex\ext;

use vitex\core\Exception;
use vitex\Vitex;

/**
 * 一个简单的Active record
 * knex.js
 * @method array getBy*(string $column, String $val)
 * @method array getAllBy*(string $column, String $val)
 */
class Model
{
    /**
     * 保存各种条件的数组.
     * @var array
     */
    private $_sql = [
        'where' => [],
        'whereraw' => [],
        'wheretuple' => [],
        'findinset' => [],
        'select' => [],
        'distinct' => [],
        'from' => '',
        'limit' => '',
        'offset' => 0,
        'group' => [],
        'having' => [],
        'union' => [],
        'join' => [],
        'order' => [],
    ];
    /**
     * 保存数据集合的数组
     * @var array
     */
    private $_post = [];
    /**
     * 保存新修改的数据
     * @var array
     */
    private $_setpost; //新修改的数据保存
    /*是否已经开启了一个事务*/
    private $_begintransaction = false;
    /**
     * 当前是否执行了查询主键的操作
     * @var boolean
     */
    private $isfind = false;
    /**
     * ORM修改数据时排除的字段
     * @var array
     */
    protected $exclude = []; //排除不修改的
    /**
     * 主键的名字
     * @var string
     */
    protected $pk = 'id';
    /**
     * 主键的值
     * @var mixed
     */
    private $pkval;
    /**
     * 当前默认的表名
     * @var string
     */
    protected $table;
    /**
     * 表前缀
     * @var string
     */
    protected $prefix = '';
    /**
     * 构造好的sql语句
     * @var string
     */
    public $sql;
    /**
     * @var \Pdo
     */
    protected $pdo;
    /**
     * @var \vitex\ext\Pdo
     */
    protected $DB;

    /**
     * 仅仅获取sql，不获取内容
     * @var bool
     */
    private $justSql = false;

    /**
     * PDO执行的statement句柄
     * @var
     */
    private $sth;

    /**
     * 上一次检索时间
     * @var
     */
    public $lastQueryAt;

    /**
     * where条件的预处理绑定参数
     * 条件存入_sql时生成占位符并记录参数，执行时统一使用prepare绑定
     * @var array
     */
    private $_bindParams = [];

    /**
     * 绑定参数的自增序号
     * @var int
     */
    private $_bindParamSeq = 0;

    public function __construct($table = '')
    {
        $this->vitex = Vitex::getInstance();
        if (isset($this->vitex->pdo)) {
            $this->DB = $this->vitex->pdo;
            $this->pdo = $this->DB->pdo;
        }
        if ($table) {
            $this->table = $table;
        } else {
            $class = explode('\\', get_class($this));
            $this->table = strtolower(end($class));
        }
    }

    /**
     * 初始化数据库连接
     * @param array $setting
     * @return Model
     */
    public function init(array $setting)
    {
        if (empty($this->vitex->pdo)) {
            $this->vitex->runMiddleware(new \vitex\ext\Pdo($setting));
            $this->DB = $this->vitex->pdo;
            $this->pdo = $this->DB->pdo;
        }
        return $this;
    }

    /**
     * 切换Model层使用的数据库连接
     * @param array $setting 数据库链接信息
     * @return self
     */
    public function changeDatabase(array $setting)
    {
        $pdoCon = new Pdo($setting);
        $pdoCon->setVitex($this->vitex);
        $this->DB = $pdoCon;
        $this->pdo = $this->DB->pdo;
        return $this;
    }

    /**
     * 定义一个新的模型数据
     * @param array $arr 模型数据
     * @return self
     */
    public function def($arr = [])
    {
        $this->_post = $arr;
        return $this;
    }

    /**
     * 处理映射字段
     * @param string $key 键值
     * @return null
     */
    public function __get($key)
    {
        return $this->_post[$key] ?? null;
    }

    public function __set($key, $val)
    {
        if ($this->isfind) {
            $this->_setpost[$key] = $val;
        }
        $this->_post[$key] = $val;
    }

    public function __isset($key)
    {
        return isset($this->_post[$key]);
    }

    public function __unset($key)
    {
        if ($this->isfind) {
            try {
                unset($this->_setpost[$key]);
            } catch (Exception $e) {
            }
        }
        unset($this->_post[$key]);
    }

    /**
     * 负责执行一些未定义的内容
     * @param string $method 方法名
     * @param array $args 数组
     * @return mixed 执行结果或者本身
     * @throws Exception
     */
    public function __call($method, $args)
    {
        //getby
        if (substr($method, 0, 5) == 'getBy') {
            $field = str_replace('getBy', '', $method);
            $val = array_shift($args);
            $this->where($field, '=', $val);
            return $this->_get();
        }
        //getAllBy
        //
        if (substr($method, 0, 8) == 'getAllBy') {
            $field = str_replace('getAllBy', '', $method);
            $val = array_shift($args);
            $this->where($field, '=', $val);
            return $this->_getAll();
        }
        //未发现方法
        throw new Exception($method . ' Not Found In Model Class', Exception::CODE_NOTFOUND_METHOD);
    }

    /**
     * 设置表前缀
     * @param string $prefix 前缀
     * @return self
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 设置为只获得sql 不查询数据
     * @param bool $bool
     * @return $this
     */
    public function setJustSql($bool = true)
    {
        $this->justSql = $bool;
        return $this;
    }

    /**
     * 返回当前对象的实例，一般用于子查询实例化model
     * @param string $prefix 表名前缀
     * @return self
     */
    public static function sub($prefix = '')
    {
        $self = new self;
        $self->setPrefix($prefix);
        return $self;
    }

    /**
     * 直接执行sql语句 @#_ 当做表前缀替换掉
     * @param string $sql sql语句
     * @return mixed 执行结果
     * @throws Exception
     */
    public function query($sql)
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        //原生SQL统一安全校验(拦截注释/分号/堆叠与危险函数)
        $this->guardSqlFragment($sql);
        $sql = str_replace('@#_', $this->prefix, $sql);
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        $this->lastQueryAt = time();
        return $this->DB->query($sql);
    }

    /**
     * 返回子查询构造的字符串
     */
    public function __tostring()
    {
        return $this->buildSql();
    }

    /**
     * 选择要查询的字段名
     * @param mixed $column 可以是字符串，多个字段用,分开，也可以是数组每个元素为一个字段，也可以是*
     * @return self
     */
    final public function select($column = '*')
    {
        if ($column == '*') {
            $this->_sql['select'][] = $column;
            return $this;
        }
        //处理字段名
        if (!is_array($column) && strpos($column, '(') === false) {
            $column = explode(',', $column);
        }
        $column = is_array($column) ? $column : [$column];

        $column = array_map([$this, 'formatColumn'], $column);
        $this->_sql['select'] = array_merge($this->_sql['select'], $column);
        return $this;
    }

    /**
     * 转义格式化字段名 主要用 `包括
     * table.field
     * table.*
     * table.field as _field
     * table.field _field
     * @param string $column 字段名
     * @return string 新的字段名
     */
    private function formatColumn($column)
    {
        $column = trim($column);
        /*调用系统函数时不处理(允许CASE WHEN等表达式，按不含盲注函数族的模式校验)*/
        if (strpos($column, '(') !== false) {
            return $this->guardIdentifier($column, false);
        }
        if (strpos($column, '.') !== false) {
            list($table, $column) = explode('.', $column);
            $table = '`' . str_replace('`', '``', $table) . '`.';
        } else {
            $table = '';
        }
        if ($column == '*') {
            return $table . $column;
        }
        if (strpos($column, ' ') === false) {
            return $table . '`' . str_replace('`', '``', $column) . '`';
        }
        $column = preg_replace('/[ ]+/', ' ', $column);
        $columns = explode(' ', $column);
        if (count($columns) == 3) {
            list($column, $as, $alias) = $columns;
        } else {
            list($column, $alias) = $columns;
        }
        return $table . '`' . str_replace('`', '``', $column) . '` as `' . str_replace('`', '``', $alias) . '`';
    }

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
    private function formatStrictColumn($column)
    {
        $column = trim((string)$column);
        if (!preg_match('/^[A-Za-z0-9_\-]+(\.[A-Za-z0-9_\-]+)*$/', $column)) {
            throw new \InvalidArgumentException('不安全的列名(仅允许字母数字下划线中划线和点):' . $column);
        }
        return $this->formatColumn($column);
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
    private function guardIdentifier($identifier, bool $withBlind = true)
    {
        $identifier = (string)$identifier;
        if (preg_match($withBlind ? self::IDENT_DANGER_PATTERN : self::SELECT_DANGER_PATTERN, $identifier)
            || (strpos($identifier, "`") !== false && preg_match('/\s/', $identifier))
            || preg_match(self::RE_LOGIC_OPS, $identifier)
        ) {
            throw new \InvalidArgumentException('不安全的标识符:' . $identifier);
        }
        $this->checkExpressionFunctions($identifier, '标识符');
        return $identifier;
    }

    /**
     * 校验join连接列(兼容旧版复合ON条件写法)
     * 旧版允许把带括号的复合ON条件拆分在join的两个列参数中
     * (如 leftJoin($t, '(a.memberid', '=', 'b.memberid and b.status=1'))，
     * 单侧括号不配平属于历史合法用法，因此不做单侧括号配平检查；
     * 黑名单(注释/子查询/危险函数/||&&逻辑运算符/反引号+空白)照常拦截，
     * 括号配平与函数白名单由join()对合并后的完整ON片段统一校验
     * @param string $column 连接列/表达式片段
     * @return string 校验通过的片段
     * @throws \InvalidArgumentException
     */
    private function guardJoinColumn($column)
    {
        $column = trim((string)$column);
        if (
            preg_match(self::SELECT_DANGER_PATTERN, $column)
            || (strpos($column, "`") !== false && preg_match('/\s/', $column))
            || preg_match(self::RE_LOGIC_OPS, $column)
        ) {
            throw new \InvalidArgumentException('不安全的join条件列:' . $column);
        }
        return $column;
    }

    /**
     * 校验join的完整ON条件片段(合并col/op/col2后)
     * 兼容旧版把复合ON条件拆分在两个列参数中的写法：
     * 括号配平与函数白名单必须对合并后的完整片段校验，
     * 两侧片段的黑名单由guardJoinColumn负责，操作符由checkOp白名单负责
     * @param string $col 第一个连接列/片段
     * @param string $op 操作符(已经checkOp白名单校验)
     * @param string $col2 第二个连接列/片段
     * @throws \InvalidArgumentException
     */
    private function guardJoinCondition($col, $op, $col2)
    {
        $this->checkExpressionFunctions((string)$col . ' ' . (string)$op . ' ' . (string)$col2, 'join条件');
    }

    /**
     * 校验进入条件位置(where/having/order/group)的透传列名/表达式
     * 在guardIdentifier基础上额外限制字符集: 禁止比较与逻辑运算符(=<>!&|^~)，
     * 比较运算必须经由操作符白名单参数传入，防止 (1)=(1) 式恒真条件拼接
     * @param string $column
     * @return string
     * @throws \InvalidArgumentException
     */
    private function guardConditionColumn($column)
    {
        $column = $this->guardIdentifier($column);
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
    private function checkExpressionFunctions($id, $label)
    {
        $id = (string)$id;
        if (strpos($id, '(') === false) {
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
     * 校验构建期的标识符(已经formatColumn/formatStrictColumn处理过的存储形式)
     * 完整反引号包裹的形式(含 点号/别名/星号)是安全的字面标识符直接放行，
     * 其余(函数表达式/透传片段)按guardIdentifier黑名单校验
     * 用于构建期防线：即使_sql被setSqlSet等方式注入了未校验的数据也会在构建时拦截
     * @param string $id 已格式化的标识符
     * @return string
     * @throws \InvalidArgumentException
     */
    private function guardBuiltIdentifier($id, bool $withBlind = true)
    {
        $id = (string)$id;
        if ($id === '*' || preg_match('/^`[^`]+`(\.`[^`]+`)?(\.[*])?( as `[^`]+`)?$/', $id)) {
            return $id;
        }
        //括号配平的函数/聚合表达式(如 max(`id`) as info)：select字段允许CASE WHEN等，其余场景按完整黑名单
        if (strpos($id, "(") !== false && substr_count($id, '(') === substr_count($id, ')')) {
            if ($withBlind) {
                return $this->guardExpression($id);
            }
            if (preg_match(self::SELECT_DANGER_PATTERN, $id)) {
                throw new \InvalidArgumentException('不安全的表达式:' . $id);
            }
            return $id;
        }
        return $this->guardIdentifier($id, $withBlind);
    }

    /**
     * 校验函数/聚合表达式：按guardIdentifier同款完整黑名单校验，
     * 并追加逻辑运算符拦截与函数白名单校验(括号配平+白名单)，
     * 但不检查 反引号+空白 规则(合法聚合表达式如 max(`id`) as info 含该形式)
     * @param string $id 表达式
     * @return string
     * @throws \InvalidArgumentException
     */
    private function guardExpression($id)
    {
        if (preg_match(self::IDENT_DANGER_PATTERN, $id) || preg_match(self::RE_LOGIC_OPS, $id)) {
            throw new \InvalidArgumentException('不安全的表达式:' . $id);
        }
        $this->checkExpressionFunctions($id, '表达式');
        return $id;
    }

    /**
     * 校验构建期的条件值：必须是占位符形式，否则按原生片段(light)校验
     * @param mixed $val 存储的条件值
     * @return mixed
     * @throws \InvalidArgumentException
     */
    private function guardBuiltValue($val)
    {
        if (is_array($val)) {
            foreach ($val as $v) {
                $this->guardBuiltValue($v);
            }
            return $val;
        }
        $val = (string)$val;
        if (!preg_match('/^:[A-Za-z0-9_]+$/', $val)) {
            $this->guardSqlFragment($val, true);
        }
        return $val;
    }

    /**
     * 危险模式的组合式构建块(统一维护，避免多处正则失同步)
     */
    /** 注释符与语句分隔符 */
    public const RE_TERMINATORS = '--|\/\*|;|#';
    /** 时间盲注/文件读写/信息泄露函数(词形式，反引号内同样命中) */
    public const RE_DANGER_FUNCS = '\bsleep\b|\bbenchmark\b|\bextractvalue\b|\bupdatexml\b|\bload_file\b|\boutfile\b|\bdumpfile\b|\bdatabase\b|\bversion\b|\bcurrent_user\b|\bsession_user\b|\bsystem_user\b|\bconnection_id\b|\blast_insert_id\b';
    /** 子查询关键字(原生片段严格模式/标识符场景使用) */
    public const RE_SUBQUERY = '\bselect\b|\bunion\b|\bdelete\b|\bupdate\b|\binsert\b|\bdrop\b';
    /** 盲注函数族(标识符/表达式场景拦截，合法场景如ifnull/nullif经词边界不受影响) */
    public const RE_BLIND_FUNCS = '\bif\b|\bcase\b|\bascii\b|\bord\b|\bsubstr\b|\bsubstring\b|\bmid\b|\bchar\b|\belt\b';
    /** 锁类函数(时间盲注原语，get_lock可替代sleep做延时)，应用SQL无合法用途 */
    public const RE_LOCK_FUNCS = '\bget_lock\b|\brelease_lock\b|\bis_free_lock\b|\bis_used_lock\b';
    /** 编码/数据外泄辅助函数(条件/字段透传场景无合法用途，可逐列导出数据) */
    public const RE_EXFIL_FUNCS = '\bhex\b|\bunhex\b|\bconv\b|\bbin\b|\baes_encrypt\b|\baes_decrypt\b|\bencode\b|\bdecode\b|\bcompress\b|\buncompress\b';
    /** 逻辑运算符拼接口(如(1)||(1)在MySQL中等价于OR，产生恒真条件) */
    public const RE_LOGIC_OPS = '/\|\||&&/';

    /**
     * 引号字符串字面量(支持反斜杠转义)，用于在检测逻辑拼接特征前剥离字符串内容，
     * 避免误伤合法片段中字符串值包含关键字的场景；字符串字面量在SQL中是惰性数据
     */
    public const RE_QUOTED_LITERALS = '/\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"/';

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
        'left', 'right', 'substr', 'substring', 'mid', 'substring_index', 'lpad', 'rpad', 'repeat', 'space', 'instr', 'locate', 'position', 'field', 'format',
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
     * 恒定拦截: 注释符(-- /* #)、语句分隔符(;)、时间盲注/文件/信息泄露函数、
     * 锁类函数(get_lock等时间盲注原语)、||/&&/^/~/单独! 逻辑位运算符(恒真拼接口)、
     * procedure analyse、handler、load data(危险语句)
     * allowSelect=false 时额外拦截 select/union/delete/update/insert/drop
     * 完整语句/子查询场景(select合法)请传 true；字符串子查询请使用 guardSubQuery
     * @param string $sql SQL片段
     * @param bool $allowSelect 是否允许select/union等子查询关键字
     * @return string 校验通过的SQL片段
     * @throws \InvalidArgumentException
     */
    private function guardSqlFragment($sql, bool $allowSelect = true)
    {
        $sql = (string)$sql;
        if (preg_match(self::RAW_DANGER_PATTERN, $sql)) {
            throw new \InvalidArgumentException('不安全的SQL片段:' . $sql);
        }
        //剥离引号字符串字面量后再检测逻辑拼接特征(字符串内容为惰性数据，不参与SQL逻辑)
        $stripped = preg_replace(self::RE_QUOTED_LITERALS, '', $sql) ?? '';
        if (
            //||/&&/^(xor)/~/单独!(非!=) 在MySQL中均为逻辑/位运算拼接口，可构造恒真条件
            preg_match('/\|\||&&|[\^~]|!(?!=)/', $stripped)
            //优化器/文件句柄类危险语句
            || preg_match('/\bprocedure\s+analyse\b/i', $stripped)
            || preg_match('/\bhandler\b/i', $stripped)
            || preg_match('/\bload\s+data\b/i', $stripped)
        ) {
            throw new \InvalidArgumentException('不安全的SQL片段(不允许逻辑位运算符/危险语句):' . $sql);
        }
        //锁类函数(get_lock等)是sleep之外的替代时间盲注原语，应用SQL无合法用途，全场景拦截
        if (preg_match('/(' . self::RE_LOCK_FUNCS . ')/i', $sql)) {
            throw new \InvalidArgumentException('不安全的SQL片段(不允许锁函数):' . $sql);
        }
        if (!$allowSelect && preg_match('/(' . self::RE_SUBQUERY . '|' . self::RE_BLIND_FUNCS . '|' . self::RE_EXFIL_FUNCS . ')/i', $sql)) {
            //严格模式(whereRaw/whereTuple等条件片段)额外拦截:
            //盲注函数族(if/case/ascii/substr等)、编码外泄函数(hex/conv等)
            throw new \InvalidArgumentException('不安全的SQL片段(不允许子查询/盲注/编码外泄函数):' . $sql);
        }
        return $sql;
    }

    /**
     * 校验字符串子查询(whereExists/whereIn/union等子查询场景)
     * 在guardSqlFragment基础上额外禁用盲注函数族(if/case/ascii/substr等)与
     * 编码外泄函数族(hex/unhex/conv等)：合法子查询不需要这些原语，
     * 而它们是子查询场景布尔盲注/逐列导出数据的核心工具
     * (检测前剥离引号字符串字面量，避免误伤字符串值中包含关键字的合法子查询)
     * @param string $sql 子查询SQL(不含最外层括号)
     * @return string 校验通过的子查询SQL
     * @throws \InvalidArgumentException
     */
    private function guardSubQuery($sql)
    {
        $sql = $this->guardSqlFragment($sql, true);
        $stripped = preg_replace(self::RE_QUOTED_LITERALS, '', (string)$sql) ?? '';
        if (preg_match('/(' . self::RE_BLIND_FUNCS . '|' . self::RE_EXFIL_FUNCS . ')/i', $stripped)) {
            throw new \InvalidArgumentException('不安全的SQL片段(子查询中不允许盲注/编码外泄函数):' . $sql);
        }
        return $sql;
    }

    /**
     * 判断字符串值是否为子查询(select ... from ...形式)
     * 用于whereIn/whereNotIn兼容旧版字符串子查询写法
     * @param string $query 值
     * @return bool
     */
    private function isSubQuery($query)
    {
        return (bool)preg_match("/select\s+(.?)+\s+from./msi", (string)$query);
    }

    /**
     * 校验条件位置的原生SQL片段(whereRaw/whereTuple等条件透传场景)
     * 不含子查询关键字的片段按严格模式校验(拦截子查询/盲注/编码外泄函数)；
     * 含select等子查询关键字的片段(如 in (select ...)/exists (select ...) 等
     * 文档化的合法读子查询写法)按guardSubQuery规则校验：
     * 仍拦截注释符/堆叠语句/时间盲注与锁函数/盲注与编码外泄函数/逻辑位运算符，
     * 仅放行子查询关键字(信任级别与from/join表名的select子查询一致)
     * @param string $sql SQL条件片段
     * @return string 校验通过的SQL片段
     * @throws \InvalidArgumentException
     */
    private function guardRawCondition($sql)
    {
        $sql = (string)$sql;
        //剥离引号字符串字面量后检测子查询关键字(字符串值中的关键字是惰性数据)
        $stripped = preg_replace(self::RE_QUOTED_LITERALS, '', $sql) ?? '';
        if (preg_match('/(' . self::RE_SUBQUERY . ')/i', $stripped)) {
            return $this->guardSubQuery($sql);
        }
        return $this->guardSqlFragment($sql, false);
    }

    /**
     * 严格校验表名(白名单)
     * 仅允许 表名/库.表名，以及可选的单个别名： tbl / db.tbl / tbl t / db.tbl t
     * 字符集与formatStrictColumn一致(字母/数字/下划线/中划线/点)，禁止空格、括号、反引号等一切其他字符
     * 不支持子查询等复杂表名，需要子查询请使用 Model::sub() 对象方式
     * @param string $table 表名
     * @return string 校验通过的表名
     * @throws \InvalidArgumentException
     */
    private function wrapTableName($table)
    {
        $table = trim((string)$table);
        if ($table === '') {
            throw new \InvalidArgumentException('表名不得为空');
        }
        if (!preg_match('/^[A-Za-z0-9_\-]+(?:\.[A-Za-z0-9_\-]+)*(?:\s+[A-Za-z0-9_\-]+)?$/', $table)) {
            throw new \InvalidArgumentException('不安全的表名(仅允许字母数字下划线中划线点以及可选别名):' . $table);
        }
        return $table;
    }

    /**
     * 校验表名(如from的表，可能携带子查询)的安全性
     * 表名参数允许携带select子查询(文档化用法)，拦截注释符、语句分隔符与时间/文件/信息泄露函数
     * @param string $table 表名或子查询
     * @return string 校验通过的表名
     * @throws \InvalidArgumentException
     */
    private function guardTableIdentifier($table)
    {
        $table = (string)$table;
        if (preg_match(self::RAW_DANGER_PATTERN, $table)) {
            throw new \InvalidArgumentException('不安全的表名:' . $table);
        }
        return $table;
    }

    /**
     * 基本的where查询条件,与前面的操作使用and连接
     * @param string /array $key 条件列名
     * @param string $op 操作符 = != > like等
     * @param string $val 值
     * @return self
     */
    public function where($key, $op = '', $val = '')
    {
        return $this->_where("where", $key, $op, $val);
    }

    /**
     * 基本的or where查询条件,与前面的操作使用or连接
     * @param string /array $key 条件列名
     * @param string $op 操作符 = != > like等
     * @param string $val 值
     * @return self
     */
    public function orWhere($key, $op = '', $val = '')
    {
        return $this->_where("orWhere", $key, $op, $val);
    }

    /**
     * 基本的whereIn查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param mixed $val 值
     * @return Model
     * @throws Exception
     * @throws \Error
     */
    public function whereIn($key, $val)
    {
        if (!$val) {
            throw new Exception("whereIn方法的第二个参数不得为空", Exception::CODE_PARAM_VALUE_ERROR);
        }
        return $this->_where("whereIn", $key, $val);
    }

    /**
     * 多个查询条件会当做一组条件来处理
     *  $this->whereTuple(Model::sub()->where("name","=","xx")->where("age","=",100))->orWhere("name","=","john")
     *  =>
     *  ( name="xx" and age = 100) or name="john"
     * @param $obj Model
     * @return $this
     */
    public function whereTuple($obj)
    {
        $params = ($obj instanceof self) ? $obj->_bindParams : [];
        $where = $obj->buildWhere();
        if ($where) {
            $where = (string)substr($where, 6);
        }
        //子查询的占位参数重写key后合并到当前查询，执行时统一prepare绑定
        $where = $this->mergeBindParams($where, $params);
        $this->_sql['wheretuple'][] = [$where, 'and'];
        return $this;
    }

    /**
     * 多个查询条件会当做一组条件来处理
     *  $this->orWhereTuple(Model::sub()->where("name","=","xx")->where("age","=",100))->orWhere("name","=","john")
     *  =>
     *  ( name="xx" and age = 100) or name="john"
     * @param $obj Model
     * @return $this
     */
    public function orWhereTuple($obj)
    {
        $params = ($obj instanceof self) ? $obj->_bindParams : [];
        $where = $obj->buildWhere();
        if ($where) {
            $where = (string)substr($where, 6);
        }
        //子查询的占位参数重写key后合并到当前查询，执行时统一prepare绑定
        $where = $this->mergeBindParams($where, $params);
        $this->_sql['wheretuple'][] = [$where, 'or'];
        return $this;
    }


    /**
     * 基本的whereNotIn查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param string $val 值
     * @return Model
     * @throws Exception
     * @throws \Error
     */
    public function whereNotIn($key, $val)
    {
        if (!$val) {
            throw new Exception("orWhereIn方法的第二个参数不得为空", Exception::CODE_PARAM_VALUE_ERROR);
        }
        return $this->_where("whereNotIn", $key, $val);
    }

    /**
     * 基本的or whereIn查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @param string $val 值
     * @return self
     */
    public function orWhereIn($key, $val)
    {
        return $this->_where("orWhereIn", $key, $val);
    }

    /**
     * 基本的or whereNotIn查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @param string $val 值
     * @return self
     */
    public function orWhereNotIn($key, $val)
    {
        return $this->_where("orWhereNotIn", $key, $val);
    }

    /**
     * 基本的where is null查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @return self
     */
    public function whereNull($key)
    {
        return $this->_where("whereNull", $key);
    }

    /**
     * 基本的where is not null查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @return self
     */
    public function whereNotNull($key)
    {
        return $this->_where("whereNotNull", $key);
    }

    /**
     * 基本的or where is null查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @return self
     */
    public function orWhereNull($key)
    {
        return $this->_where("orWhereNull", $key);
    }

    /**
     * 基本的or where is not null查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @return self
     */
    public function orWhereNotNull($key)
    {
        return $this->_where("orWhereNotNull", $key);
    }

    /**
     * 基本的where exists(select name form user where id=1)查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param string $val 值,如说明,不要包含最外层的 ()
     * @return self
     */
    public function whereExists($key, $val)
    {
        return $this->_where("whereExists", $key, $val);
    }

    /**
     * 基本的where not exists(select name form user where id=1)查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param string $val 值,如说明,不要包含最外层的 ()
     * @return self
     */
    public function whereNotExists($key, $val)
    {
        return $this->_where("whereNotExists", $key, $val);
    }

    /**
     * 基本的 or where exists(select name form user where id=1)查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param string $val 值,如说明,不要包含最外层的 ()
     * @return self
     */
    public function orWhereExists($key, $val)
    {
        return $this->_where("orWhereExists", $key, $val);
    }

    /**
     * 基本的 or where not exists(select name form user where id=1)查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param string $val 值,如说明,不要包含最外层的 ()
     * @return self
     */
    public function orWhereNotExists($key, $val)
    {
        return $this->_where("orWhereNotExists", $key, $val);
    }

    /**
     * 基本的where between a and b查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param array $val ,这是一个数组,包含两个元素 between $val[0] and $val[1]
     * @return self
     */
    public function whereBetween($key, array $val)
    {
        return $this->_where("whereBetween", $key, $val);
    }

    /**
     * 基本的where not between a and b查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param array $val ,这是一个数组,包含两个元素 not between $val[0] and $val[1]
     * @return self
     */
    public function whereNotBetween($key, array $val)
    {
        return $this->_where("whereNotBetween", $key, $val);
    }

    /**
     * 基本的or where between a and b查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @param array $val ,这是一个数组,包含两个元素 between $val[0] and $val[1]
     * @return self
     */
    public function orWhereBetween($key, array $val)
    {
        return $this->_where("orWhereBetween", $key, $val);
    }

    /**
     * 基本的or where not between a and b查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @param array $val ,这是一个数组,包含两个元素 not between $val[0] and $val[1]
     * @return self
     */
    public function orWhereNotBetween($key, array $val)
    {
        return $this->_where("orWhereNotBetween", $key, $val);
    }

    /**
     * where查询语句，支持子查询等
     * @param  $method
     * @param string $key 键值
     * @param string $op 操作符
     * @param string $val
     * @return $this    错误信息
     * @throws \Error
     * @internal param $string /array/callable $val    值
     */
    private function _where($method, $key, $op = '', $val = '')
    {
        $where = ['where' => '', 'whereIn' => 'in', 'whereNotIn' => 'not in', 'whereNull' => 'is', 'whereNotNull' => 'is not', 'whereExists' => 'exists', 'whereNotExists' => 'not exists', 'whereBetween' => 'between', 'whereNotBetween' => 'not between', 'orWhere' => '', 'orWhereIn' => 'in', 'orWhereNotIn' => 'not in', 'orWhereNull' => 'is', 'orWhereNotNull' => 'is not', 'orWhereExists' => 'exists', 'orWhereNotExists' => 'not exists', 'orWhereBetween' => 'between', 'orWhereNotBetween' => 'not between'];

        if (!isset($where[$method])) {
            throw new \Error('找不到您要执行的方法' . $method);
        }
        //兼容 where 直接传递一个关联数组的情况
        if (($method == 'where' || $method == 'orWhere') && is_array($key)) {
            foreach ($key as $k => $v) {
                $this->_where($method, $k, '=', $v);
            }
            return $this;
        }
        if (strpos($method, 'xists') === false) {
            //where条件列允许函数表达式(如UNIX_TIMESTAMP(...)算术列)
            $key = $this->formatColumn($key);
            if (is_string($key) && strpos($key, "(") !== false) {
                //条件位置表达式按完整黑名单校验并禁止比较/逻辑运算符(防恒真条件拼接)
                $this->guardConditionColumn($key);
            }
        } else {
            //exists子查询(以key传入，如 whereExists('select id from t where ...'))
            //统一安全校验(拦截注释/分号/危险函数/盲注与编码外泄函数)
            $this->guardSubQuery((string)$key);
        }
        //非这俩方法时不需要指定操作符
        if ($method != 'where' && $method != 'orWhere') {
            $val = $op;
        } else {
            //普通where操作符白名单校验，防止通过操作符注入SQL
            $op = $this->checkOp($op);
        }
        //设定操作符和连接符
        $type = strpos($method, 'or') !== false ? ' or ' : ' and ';
        $_op = $where[$method];
        $op = $_op ? $_op : $op;
        if ($op == 'is' || $op == 'is not') {
            $val = 'null';
        }

        /**
         * 值统一使用占位符预处理绑定，不再内联拼接进SQL
         * exists的值为子查询语句本身，保持原样拼接
         */
        if ($op == 'exists' || $op == 'not exists') {
            //字符串子查询统一安全校验(子查询合法，拦截注释/分号/危险函数/盲注与编码外泄函数)
            $val = is_array($val) ? $val : $this->guardSubQuery((string)$val);
        } elseif ($op == 'is' || $op == 'is not') {
            $val = 'null';
        } elseif (($op == 'in' || $op == 'not in') && is_string($val) && $this->isSubQuery($val)) {
            //兼容旧版 in/not in('select ...')字符串子查询写法：按子查询级校验后整体内联
            //(拦截注释/分号/危险函数/盲注与编码外泄函数，放行select)
            $val = $this->guardSubQuery($val);
        } elseif (is_object($val)) {
            //子查询对象(如Model::sub()生成的)：构建SQL并合并其中的占位参数，子查询整体内联
            $subParams = ($val instanceof self) ? $val->_bindParams : [];
            $val = $this->mergeBindParams((string)$val, $subParams);
        } elseif (is_array($val)) {
            //in/not in/between 多个值每个值生成一个占位符
            foreach ($val as $_k => $_v) {
                $val[$_k] = $this->addBindParam($_v);
            }
        } else {
            $val = $this->addBindParam($val);
        }

        $this->_sql['where'][] = [$key, $op, $val, $type];
        return $this;
    }

    /**
     * 记录一个绑定参数并返回占位符
     * @param mixed $val 参数值
     * @return string 占位符 :w_N
     */
    private function addBindParam($val)
    {
        $this->_bindParamSeq++;
        $key = 'w_' . $this->_bindParamSeq;
        $this->_bindParams[$key] = $val;
        return ':' . $key;
    }

    /**
     * 合并一段外部SQL片段(如子查询)中的占位参数
     * 片段中的占位符会被重写为不冲突的新key后合并进来
     * @param string $sql 包含 :w_N 形式占位符的SQL片段
     * @param array $params 占位数据 key=>val (key不含:前缀)
     * @return string 重写占位符之后的SQL片段
     */
    private function mergeBindParams(string $sql, array $params): string
    {
        if (!$params) {
            return $sql;
        }
        foreach ($params as $key => $val) {
            $this->_bindParamSeq++;
            //使用mw_前缀避免与待合并的w_N旧key同名，防止连环替换
            $newKey = 'mw_' . $this->_bindParamSeq;
            //整词匹配占位符，避免误替换相近的key
            $sql = preg_replace('/:' . preg_quote((string)$key, '/') . '(?![A-Za-z0-9_])/', ':' . $newKey, $sql);
            $this->_bindParams[$newKey] = $val;
        }
        return $sql;
    }

    /**
     * 校验操作符是否在白名单内，防止通过操作符注入SQL
     * @param string $op 操作符 = != <> > < >= <= like
     * @return string 校验后的操作符
     * @throws \InvalidArgumentException
     */
    private function checkOp($op): string
    {
        static $allowedOps = ['=', '!=', '<>', '>', '<', '>=', '<=', 'like'];
        $op = strtolower(trim((string)$op));
        if (!in_array($op, $allowedOps, true)) {
            throw new \InvalidArgumentException('不支持的操作类型:' . $op);
        }
        return $op;
    }

    /**
     * 字符串形式的查询语句
     * @param string $val 查询语句
     * @return self
     */
    public function whereRaw($val)
    {
        //原生片段统一安全校验(条件片段)：含select等子查询关键字的合法读子查询片段
        //(如 in (select ...))按guardRawCondition放行，仍拦截盲注/外泄/锁函数与注释堆叠
        $this->_sql['whereraw'][] = $this->guardRawCondition($val);
        return $this;
    }

    /**
     * set数据查询
     * @param string $column 字段名
     * @param mixed $val 查询值
     * @param string $type 类型 默认and
     * @return self
     */
    public function findInSet($column, $val, $type = 'and')
    {
        //连接符白名单(type会原样拼入SQL，防止注入)
        $type = strtolower(trim((string)$type));
        if (!in_array($type, ['and', 'or'], true)) {
            throw new \InvalidArgumentException('不安全的连接类型，仅允许 and/or:' . $type);
        }
        $column = $this->formatStrictColumn($column);
        //值使用占位符预处理绑定
        $val = $this->addBindParam($val);
        $this->_sql['findinset'][] = [$column, $val, $type];
        return $this;
    }

    /**
     * or set查询
     * @param  $column
     * @param  $val
     * @return self
     */
    public function orFindInSet($column, $val)
    {
        return $this->findInSet($column, $val, 'or');
    }

    /**
     * Having分组操作条件
     * @param string $key 键值
     * @param string $op 操作符
     * @param array /callable $val 操作值
     * @param string $type 类型 and/or
     * @return self
     */
    public function having($key, $op, $val, $type = "AND")
    {
        //having键支持聚合表达式(如sum(pv))，按表达式级校验(拦危险词)
        $key = $this->formatColumn($key);
        $op = $this->checkOp($op);
        //值使用占位符预处理绑定
        $val = $this->addBindParam($val);
        $this->_sql['having'][] = [$key, $op, $val, $type];
        return $this;
    }

    /**
     * 要查询的表名
     * 仅允许 表名/库.表名 以及可选别名，字符集为字母数字下划线中划线
     * @param string $table 表名
     * @return self
     * @throws \InvalidArgumentException
     */
    final public function from($table)
    {
        $table = (string)$table;
        //入口级严格白名单校验，表名不支持子查询，需要子查询请使用Model::sub()对象方式
        $this->wrapTableName($table);
        $this->_sql['from'] = $table;
        return $this;
    }

    /**
     * 获取当前要查询的表名
     * @return string name
     * @throws \InvalidArgumentException
     */
    public function getTable()
    {
        if ($this->_sql['from']) {
            $table = $this->_sql['from'];
        } else {
            $table = $this->table;
        }
        /**
         * 构建期统一严格白名单校验(覆盖from/setTable/构造函数所有来源)，
         * 表名不支持子查询，需要子查询请使用Model::sub()对象方式
         */
        $table = $this->wrapTableName($table);
        $table = $this->prefix . $table;
        return $this->formatTable($table);
    }

    //提取出表名中的别名
    private function formatTable($table)
    {
        $alias = '';
        if (strpos($table, ' ') !== false) {
            list($table, $alias) = explode(' ', $table);
            $alias = ' as `' . str_replace('`', '``', $alias) . '`';
        }
        return '`' . str_replace('`', '``', $table) . '`' . $alias;
    }

    /**
     * 查询的条数
     * @param string $limit 要查询的条数
     * @param integer $offset 偏移值 默认0
     * @return self
     */
    final public function limit($limit, $offset = 0)
    {
        $this->_sql['limit'] = (int)$limit;
        $this->offset($offset);
        return $this;
    }

    /**
     * 单独设置的偏移数制
     * @param integer $offset 偏移数值
     * @return self
     */
    final public function offset($offset)
    {
        $this->_sql['offset'] = (int)$offset;
        return $this;
    }

    /**
     * 排序字段以及排序方式
     * @param string $column 字段
     * @param string $way 排序方式 asc/desc
     * @return self
     * @throws \InvalidArgumentException
     */
    final public function orderBy($column, $way = "DESC")
    {
        $way = strtoupper(trim((string)$way));
        if (!in_array($way, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException('不支持的排序方式，仅允许 asc/desc:' . $way);
        }
        //兼容旧版:含括号的排序表达式(如 day(from_unixtime(create_at,'%Y-%m-%d')))按透传表达式校验
        //(黑名单+括号配平+函数白名单)；纯列名仍走严格白名单
        if (strpos((string)$column, '(') !== false) {
            $this->guardConditionColumn((string)$column);
        } else {
            $column = $this->formatStrictColumn($column);
        }
        $this->_sql['order'][] = [$column, $way];
        return $this;
    }

    /**
     * group分组操作
     * @param string $column 要分组的字段
     * @return self
     */
    final public function groupBy($column)
    {
        //兼容旧版:含括号的分组表达式(如 day(from_unixtime(create_at,'%Y-%m-%d')))按透传表达式校验
        //(黑名单+括号配平+函数白名单)；纯列名仍走严格白名单
        if (strpos((string)$column, '(') !== false) {
            $this->guardConditionColumn((string)$column);
        } else {
            $column = $this->formatStrictColumn($column);
        }
        $this->_sql['group'][] = $column;
        return $this;
    }

    /**
     * 去重查询
     * @param string /array $column 字段名
     * @return self
     */
    final public function distinct($column)
    {
        if (!is_array($column)) {
            $column = explode(',', $column);
        }
        $column = array_map([$this, 'formatColumn'], $column);

        $this->_sql['distinct'] = array_merge($this->_sql['distinct'], $column);
        return $this;
    }

    /**
     * join操作的集中执行方法
     * @param string $type 各种不同的join操作
     * @param string $table join的表明
     * @param string $col 第一个字段
     * @param string $col2 第二个字段
     * @return self
     */
    private function join($type, $table, $col, $op, $col2 = '')
    {
        $op = $this->checkOp($op);
        //兼容旧版复合ON条件:括号可拆分在col/col2两个参数中(如'(a.memberid' + 'b.memberid and b.status=1)')
        //合并后的完整ON片段须括号配平且函数在白名单内；两侧分别做黑名单校验；普通列名仍按标识符包裹
        $this->guardJoinCondition($col, $op, $col2);
        $col = $this->formatJoinColumn($col);
        $col2 = $this->formatJoinColumn($col2);
        $this->_sql['join'][$type][] = [$table, $col, $op, $col2];
        return $this;
    }

    /**
     * join连接列格式化(兼容旧版透传行为)：含括号的表达式片段透传(仅黑名单校验)，普通列名包裹反引号
     * @param string $column 连接列
     * @return string
     * @throws \InvalidArgumentException
     */
    private function formatJoinColumn($column)
    {
        $column = trim((string)$column);
        if (strpos($column, '(') !== false || strpos($column, ')') !== false) {
            return $this->guardJoinColumn($column);
        }
        return $this->formatColumn($column);
    }

    /**
     * @param string $table 表名
     * @param string $col 一个连接表的列名
     * @param string $op 操作符 = !=
     * @param string $col2 另一个连接表的列明
     * @return self
     */
    public function innerJoin($table, $col, $op, $col2 = '')
    {
        return $this->join("inner join", $table, $col, $op, $col2);
    }

    /**
     * @param string $table 表名
     * @param string $col 一个连接表的列名
     * @param string $op 操作符 = !=
     * @param string $col2 另一个连接表的列明
     * @return self
     */
    public function leftJoin($table, $col, $op, $col2 = '')
    {
        return $this->join("left join", $table, $col, $op, $col2);
    }

    /**
     * @param string $table 表名
     * @param string $col 一个连接表的列名
     * @param string $op 操作符 = !=
     * @param string $col2 另一个连接表的列明
     * @return self
     */
    public function leftOuterJoin($table, $col, $op, $col2 = '')
    {
        return $this->join("left outer join", $table, $col, $op, $col2);
    }

    /**
     * @param string $table 表名
     * @param string $col 一个连接表的列名
     * @param string $op 操作符 = !=
     * @param string $col2 另一个连接表的列明
     * @return self
     */
    public function rightJoin($table, $col, $op, $col2 = '')
    {
        return $this->join("right join", $table, $col, $op, $col2);
    }

    /**
     * @param string $table 表名
     * @param string $col 一个连接表的列名
     * @param string $op 操作符 = !=
     * @param string $col2 另一个连接表的列明
     * @return self
     */
    public function rightOuterJoin($table, $col, $op, $col2 = '')
    {
        return $this->join("right outer join", $table, $col, $op, $col2);
    }

    /**
     * @param string $table 表名
     * @param string $col 一个连接表的列名
     * @param string $op 操作符 = !=
     * @param string $col2 另一个连接表的列明
     * @return self
     */
    public function outerJoin($table, $col, $op, $col2 = '')
    {
        return $this->join("outer join", $table, $col, $op, $col2);
    }

    /**
     * @param string $table 表名
     * @param string $col 一个连接表的列名
     * @param string $op 操作符 = !=
     * @param string $col2 另一个连接表的列明
     * @return self
     */
    public function fullOuterJoin($table, $col, $op, $col2 = '')
    {
        return $this->join("full outer join", $table, $col, $op, $col2);
    }

    /**
     * @param string $table 表名
     * @param string $col 一个连接表的列名
     * @param string $op 操作符 = !=
     * @param string $col2 另一个连接表的列明
     * @return self
     */
    public function crossJoin($table, $col, $op, $col2 = '')
    {
        return $this->join("cross join", $table, $col, $op, $col2);
    }

    /**
     * union操作
     * @param string /callable $str union字符串或者一个可以tostring的对象
     * @return self
     */
    final public function union($str)
    {
        if (is_object($str)) {
            //子查询对象：先取占位参数(toString会重置条件)再构建SQL，参数合并到当前查询
            $subParams = ($str instanceof self) ? $str->_bindParams : [];
            $str = $this->mergeBindParams((string)$str, $subParams);
        } else {
            //原生SQL统一安全校验(完整SELECT合法，拦截注释/分号/危险函数/盲注与编码外泄函数)
            $str = $this->guardSubQuery((string)$str);
        }
        $this->_sql['union'][] = $str;
        return $this;
    }

    /**
     * 设置数据
     * @param string /array $key 键值
     * @param string $val 值
     * @return self
     */
    public function set($key, $val = null)
    {
        if (is_array($key)) {
            $this->_setpost = array_merge($this->_setpost, $key);
        } else {
            $this->_setpost[$key] = $val;
        }
        return $this;
    }

    /**
     * 获取当前设置的查询条件配置
     * @return array
     */
    public function getSqlSet()
    {
        return $this->_sql;
    }

    /**
     * 获取执行的sql
     */
    public function getSql()
    {
        $this->justSql = false;
        return $this->sql;
    }

    /**
     * 设置当前的查询条件配置
     * @return $this
     */
    public function setSqlSet(array $set)
    {
        $this->_sql = $set;
        return $this;
    }


    /**
     * 当第一个条件成立时则执行后面的回调方法 回调方法会接受当前控制器的实例为参数
     * @param $condition
     * @param callable $call 满足条件时执行
     * @param callable|null $notCall 不满足条件时执行
     * @return $this
     */
    public function when($condition, callable $call, callable $notCall = null)
    {
        if (is_callable($condition)) {
            $result = call_user_func($condition);
        } else {
            $result = $condition;
        }
        if ($result) {
            call_user_func_array($call, [$this]);
        } else {
            if ($notCall) {
                call_user_func_array($notCall, [$this]);
            }
        }
        return $this;
    }

    /**
     * 构建sql语句
     * @param bool $iscount
     * @return string
     * @throws Exception
     * @throws \Error
     */
    private function buildSql($iscount = false)
    {
        if (!$this->getTable()) {
            throw new \Error('您还没有指定要查询的表名');
        }
        $sql = "select ";

        if ($iscount) {
            $field = is_bool($iscount) ? '*' : $iscount;
            $field = $this->formatColumn($field);
            if ($this->_sql['distinct']) {
                if (count($this->_sql['distinct']) > 1) {
                    throw new Exception(Exception::CODE_PARAM_ERROR_MSG . 'DISTINCT 用于COUNT只能一个字段', Exception::CODE_PARAM_ERROR);
                }
                //构建期校验:防setSqlSet等方式注入未校验数据
                $sql .= 'count(distinct ' . $this->guardBuiltIdentifier($this->_sql['distinct'][0], false) . ') as num ';
            } else {
                $sql .= 'count(' . $field . ') as num ';
            }
        } else {
            //select/distinct字段允许CASE WHEN等表达式(无盲注函数族校验)
            if ($this->_sql['distinct']) {
                $distinctCols = [];
                foreach ($this->_sql['distinct'] as $v) {
                    $distinctCols[] = $this->guardBuiltIdentifier($v, false);
                }
                $sql .= 'distinct (' . implode(',', $distinctCols) . ') ';
            } else {
                if ($this->_sql['select']) {
                    $selectCols = [];
                    foreach ($this->_sql['select'] as $v) {
                        $selectCols[] = $this->guardBuiltIdentifier($v, false);
                    }
                    $field = implode(',', $selectCols);
                } else {
                    $field = '*';
                }
                $sql .= $field . ' ';
            }
        }

        $sql .= " from " . $this->getTable() . ' ';

        $sql .= $this->buildWhere();
        //groupby (构建期校验)
        if ($this->_sql['group']) {
            $sql .= ' group by ' . implode(',', array_map([$this, 'guardBuiltIdentifier'], $this->_sql['group'])) . ' ';
        }

        //having (构建期校验)
        if ($this->_sql['having']) {
            $sql .= ' having ';
            foreach ($this->_sql['having'] as $k => list($key, $op, $val, $type)) {
                $key = $this->guardBuiltIdentifier($key);
                $op = $this->checkOp($op);
                $val = $this->guardBuiltValue($val);
                if ($k != 0) {
                    $sql .= $type . ' ';
                }
                $sql .= ' ' . $key . ' ' . $op . ' ' . $val;
            }
        }

        //union (构建期校验:完整SELECT合法，拦截注释/分号/危险函数/盲注与编码外泄函数)
        if ($this->_sql['union']) {
            foreach ($this->_sql['union'] as $union) {
                $sql .= ' union (' . $this->guardSubQuery((string)$union) . ') ';
            }
        }
        //orderby (构建期校验)
        if ($this->_sql['order'] && !$iscount) {
            $sql .= ' order by ';
            $_order = [];
            foreach ($this->_sql['order'] as list($column, $way)) {
                $column = $this->guardBuiltIdentifier($column);
                $way = strtoupper(trim((string)$way));
                if (!in_array($way, ['ASC', 'DESC'], true)) {
                    throw new \InvalidArgumentException('不支持的排序方式，仅允许 asc/desc:' . $way);
                }
                $_order[] = $column . ' ' . $way;
            }
            $sql .= implode(',', $_order);
        }
        //limit (构建期校验:仅允许数字)
        if ($this->_sql['limit'] && !$iscount) {
            if (!is_numeric($this->_sql['limit']) || !is_numeric($this->_sql['offset'])) {
                throw new \InvalidArgumentException('不安全的limit/offset值');
            }
            $sql .= ' limit ' . (int)$this->_sql['offset'] . ',' . (int)$this->_sql['limit'];
        }
        //重置各种条件
        $this->resetCon();
        $this->sql = $sql;
        return $sql;
    }

    /**
     * 重新构建where条件 join/where
     * @return string 构建好的where条件
     */
    private function buildWhere()
    {
        $sql = ' ';
        //join
        if ($this->_sql['join']) {
            foreach ($this->_sql['join'] as $key => $join) {
                //构建期校验:连接类型仅允许已知join关键字
                if (!preg_match('/^(inner|left|right|left outer|right outer|outer|full outer|cross) join$/i', trim($key))) {
                    throw new \InvalidArgumentException('不安全的join类型:' . $key);
                }
                foreach ($join as list($table, $con1, $op, $con2)) {
                    $table = $this->prefix . $table;
                    if (strpos($table, '(') !== false && strpos($table, ')') !== false) {
                        //表名允许携带子查询，走表名级校验
                        $table = $this->guardTableIdentifier($table);
                    } else {
                        $table = $this->formatTable($table);
                    }
                    //构建期校验:连接条件与操作符
                    //兼容拆分括号的复合ON条件:合并后的完整ON片段配平+函数白名单，两侧黑名单校验
                    $this->guardJoinCondition($con1, $op, $con2);
                    $con1 = $this->guardJoinColumn($con1);
                    $con2 = $this->guardJoinColumn($con2);
                    $op = $this->checkOp($op);
                    $sql .= $key . ' ' . $table . ' on ' . $con1 . ' ' . $op . ' ' . $con2 . ' ';
                }
            }
        }
        //where
        if ($this->_sql['where'] || $this->_sql['whereraw'] || $this->_sql['findinset'] || $this->_sql['wheretuple']) {
            $sql .= 'where ';
            $haswhere = false;
            foreach ($this->_sql['where'] as $k => list($column, $op, $val, $type)) {
                $haswhere = true;
                if ($k !== 0) {
                    $sql .= $type . ' ';
                }
                //构建期防线：列/操作符/值校验(防setSqlSet等方式注入未校验数据)
                if ($op != 'exists' && $op != 'not exists') {
                    $column = $this->guardBuiltIdentifier($column);
                } else {
                    //构建期防线:子查询统一安全校验(拦截注释/分号/危险函数/盲注与编码外泄函数)
                    $this->guardSubQuery((string)$column);
                }
                switch ($op) {
                    case 'in':
                    case 'not in':
                        //构建期校验:值必须为占位符数组或安全的子查询片段
                        $val = $this->guardBuiltValue($val);
                        if (is_array($val)) {
                            $val = implode(',', $val);
                        }
                        $sql .= $column . ' ' . $op . ' (' . $val . ') ';
                        break;
                    case 'exists':
                    case 'not exists':
                        $sql .= ' ' . $op . "(" . $column . ') ';
                        break;
                    case 'not between':
                    case 'between':
                        //构建期校验
                        $val = $this->guardBuiltValue($val);
                        $sql .= ' ' . $column . ' ' . $op . ' ' . $val[0] . ' and ' . $val[1];
                        break;
                    case 'is not':
                    case 'is':
                        $sql .= $column . ' ' . $op . ' null ';
                        break;
                    case 'find_in_set':
                        $sql .= " find_in_set(" . $val . ",$column) ";
                        break;
                    default:
                        //构建期校验:操作符白名单 + 值必须为占位符或安全的子查询片段
                        $op = $this->checkOp($op);
                        $val = $this->guardBuiltValue($val);
                        $sql .= $column . ' ' . $op . ' ' . $val . ' ';
                }
            }
            //find_in_set值
            foreach ($this->_sql['findinset'] as $k => list($column, $val, $type)) {
                //构建期校验
                $column = $this->guardBuiltIdentifier($column);
                $val = $this->guardBuiltValue($val);
                if ($haswhere) {
                    $sql .= $type . ' ';
                    $haswhere = true;
                }
                $sql .= " find_in_set(" . $val . ",$column) ";
            }
            //where raw (构建期校验)
            foreach ($this->_sql['whereraw'] as $raw) {
                $sql .= ' ' . $this->guardRawCondition($raw) . ' ';
                $haswhere = true;
            }
            //where tuple (构建期校验)
            foreach ($this->_sql['wheretuple'] as list($raw, $type)) {
                $raw = $this->guardRawCondition((string)$raw);
                if ($haswhere) {
                    $sql .= $type . ' (' . $raw . ') ';
                } else {
                    $sql .= ' (' . $raw . ') ';
                    $haswhere = true;
                }
            }
        }
        return $sql;
    }

    /**
     * 根据where条件修改内容
     * @param array $arr 要修改的数据 关联数组
     * @return mixed 执行结果
     * @throws Exception
     */
    final public function update(array $arr)
    {
        if (!$this->pdo) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        //修改
        $sql = "update " . $this->getTable() . " set ";
        $sets = [];
        foreach ($arr as $key => $val) {
            //update字段名使用严格白名单校验
            $sets[] = $this->formatStrictColumn($key) . ' = :' . $key;
        }
        $sql .= implode(',', $sets);
        $sql .= $this->buildWhere();
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        $this->sth = $this->pdo->prepare($sql);
        //set数据 + where条件的绑定参数
        $ret = $this->sth->execute($arr + $this->_bindParams);
        $this->lastQueryAt = time();
        $this->resetCon();
        return $ret ? $this->sth->rowCount() : $ret;
    }

    /**
     * 要插入数据库的数据，可以是多维数组
     * 当为二维数组的时候插入多条数据
     * @param array $arr 关联数组或者二维数组
     * @return int 成功则返回最后插入的ID
     * @throws Exception
     */
    final public function insert(array $arr = [])
    {
        if (!$this->pdo) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_UNCONNECT_DATABASE);
        }
        $keys = [];
        if (count($arr) === 0) {
            return false;
        }
        $temp = $arr;
        $ele = array_pop($temp);
        if (is_array($ele)) {
            $keys = array_keys($ele);
        } else {
            $keys = array_keys($arr);
            $arr = [$arr];
        }
        //整理keys
        $params = array_map(function ($item) {
            return ':' . $item;
        }, $keys);
        //insert字段名使用严格白名单校验
        $keys = array_map(function ($item) {
            return $this->formatStrictColumn($item);
        }, $keys);

        $sql = "insert into " . $this->getTable() . " (" . implode(',', $keys) . ") values (" . implode(',', $params) . ")";
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        $sth = $this->pdo->prepare($sql);
        $lastid = null;
        !$this->_begintransaction && count($arr) > 1 && $this->pdo->beginTransaction();
        foreach ($arr as $val) {
            $sth->execute($val);
            $lastid = $this->pdo->lastInsertId();
        }
        !$this->_begintransaction && count($arr) > 1 && $this->pdo->commit();
        $this->lastQueryAt = time();
        $this->resetCon();
        return $lastid;
    }

    /**
     * 是否已经开启事务
     * @return boolean
     */
    final public function hasBeginTransaction()
    {
        return $this->_begintransaction;
    }

    /**
     * 事务开始启动事务
     * @return self
     * @throws \vitex\core\Exception
     */
    final public function begin()
    {
        if (!$this->pdo) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        if ($this->_begintransaction) {
            throw new Exception("已经开启了一个事务，请勿重新开启", Exception::CODE_DATABASE_ERROR);
        }
        $this->pdo->beginTransaction();
        $this->_begintransaction = true;
        return $this;
    }

    /**
     * 提交事务
     * @return bool
     * @throws Exception
     */
    final public function commit()
    {
        if (!$this->pdo) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        $this->_begintransaction = false;
        return $this->pdo->commit();
    }

    /**
     * 回滚事务
     * @return $this
     * @throws Exception
     */
    final public function rollBack()
    {
        if (!$this->pdo) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        if ($this->_begintransaction) {
            $this->pdo->rollBack();
            $this->_begintransaction = false;
        }
        return $this;
    }

    /**
     * ORM似的保存
     * 保存当前模型，如果存在主键则尝试修改，如果不存在主键则尝试新建
     * @param string $id 主键的值
     * @return mixed  执行结果
     */
    final public function save($id = '')
    {
        $pkval = $id ?: $this->pkval;
        //有修改的数据
        if ($this->_setpost && $pkval) {
            //排除不修改的字段
            foreach ($this->exclude as $key => $exclude) {
                if (isset($this->_setpost[$key]) || $key == $this->pk) {
                    unset($this->_setpost[$key]);
                }
            }
            return $this->where($this->pk, '=', $pkval)->update($this->_setpost);
        }
        //保存新数据
        if ($this->_post) {
            return $this->insert($this->_post);
        }
        return null;
    }

    /**
     * 删除数据
     * @return bool
     * @throws Exception
     * @throws \Error
     */
    final public function delete()
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        $sql = "delete from " . $this->getTable() . " ";
        $where = $this->buildWhere();
        //条件判断
        //没有外部条件的时候查询当前对象是否有查询过的模型
        if (!$where && $this->isfind) {
            $this->where($this->pk, '=', $this->pkval);
            $where = $this->buildWhere();
        }
        if (!$where) {
            throw new \Error('删除全部数据请使用truncate方法', Exception::CODE_DATABASE_ERROR);
        }
        $sql .= $where;
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        $ret = $this->DB->execute($sql, $this->_bindParams);
        $this->resetCon();
        return $ret;
    }

    /**
     * 清空当前指定的表
     * @return bool
     * @throws Exception
     */
    final public function truncate()
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        $table = $this->getTable();
        $sql = "truncate table " . $table;
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        return $this->DB->execute($sql);
    }

    /**
     * 自增一个字段
     * @param mixed $column 字段名,可以使用一个数组传递多个字段
     * @param mixed $amount 自增的数制默认为1，如果是一个数组则对应前面的字段也必须为数组，如果column为数组此参数不为数组则默认所有字段增加相同的值
     * @return bool  执行sql的结果
     */
    final public function increment($column, $amount = 1)
    {
        return $this->stepField($column, $amount);
    }

    /**
     * 自减一个字段
     * @param mixed $column 字段名,可以使用一个数组传递多个字段
     * @param mixed $amount 自增的数制默认为1，如果是一个数组则对应前面的字段也必须为数组，如果column为数组此参数不为数组则默认所有字段增加相同的值
     * @return bool  执行sql的结果
     */
    final public function decrement($column, $amount = 1)
    {
        if (is_array($amount)) {
            $amount = array_map(function ($item) {
                return (0 - $item);
            }, $amount);
        } else {
            $amount = 0 - $amount;
        }
        return $this->stepField($column, $amount);
    }

    /**
     * 自减/增一个字段
     * @param mixed $column 字段名,可以使用一个数组传递多个字段
     * @param mixed $amount 自增的数制默认为1，如果是一个数组则对应前面的字段也必须为数组，如果column为数组此参数不为数组则默认所有字段增加相同的值
     * @return bool                    执行sql的结果
     * @throws \vitex\core\Exception
     */
    private function stepField($column, $amount)
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        //自增的数值只允许数字，防止拼接注入
        if (is_array($amount)) {
            foreach ($amount as $_num) {
                if (!is_numeric($_num)) {
                    throw new Exception("自增的数值必须是数字", Exception::CODE_PARAM_VALUE_ERROR);
                }
            }
        } elseif (!is_numeric($amount)) {
            throw new Exception("自增的数值必须是数字", Exception::CODE_PARAM_VALUE_ERROR);
        }
        if (is_array($column)) {
            if (is_array($amount) && count($amount) != count($column)) {
                throw new Exception("传递的字段与自增值无法对应，请查看数量", Exception::CODE_PARAM_NUM_ERROR);
            }
        } else {
            $column = [$column];
        }

        $sql = "update " . $this->getTable() . " set ";
        $sets = [];
        foreach ($column as $key => $val) {
            if (is_array($amount)) {
                $num = $amount[$key];
            } else {
                $num = $amount;
            }
            //自增字段名使用严格白名单校验
            $val = $this->formatStrictColumn($val);
            $sets[] = $val . " = (" . $val . " + " . $num . ") ";
        }
        $sql .= implode(',', $sets);
        $sql .= $this->buildWhere();
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        $ret = $this->DB->execute($sql, $this->_bindParams);

        $this->resetCon();
        return $ret;
    }

    /**
     * 统计数量
     * @param string $column 字段名
     * @return int 数量
     * @throws Exception
     * @throws \Error
     */
    public function count($column = '*')
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        //buildSql会重置条件，先快照绑定参数
        $params = $this->_bindParams;
        $sql = $this->buildSql($column);
        $this->sql = $sql;
        /**
         * 仅仅获取sql，不实际执行
         */
        if ($this->justSql) {
            return 0;
        }
        $info = $this->DB->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        $this->lastQueryAt = time();
        return $info['num'] ?? 0;
    }

    /**
     * 一个简化的array_map操作，可以按照指定的字段返回一个仅包含该字段的数组
     * @param string $column 字段名
     * @return array  返回数组
     */
    final public function pluck($column)
    {
        $infos = $this->getAll();
        $info = array_map(function ($val) use ($column) {
            return $val[$column];
        }, $infos);
        return $info;
    }

    /**
     * 用于查询返回记录行中的一个字段，仅包含一个字段
     * @param $column string 字段名称 只能为 field 或者为table.field的形式
     * @return string
     * @throws Exception
     */
    final public function field($column)
    {
        $key = $column;
        if (strpos($column, '.') !== false) {
            list(, $key) = explode('.', $column);
        }
        $key = str_replace('`', '', $key);
        $this->select($column);
        $info = $this->get();
        if (!isset($info[$key])) {
            throw  new Exception('参数错误，您的参数只能为 field 或者 table.field的形式', Exception::CODE_PARAM_VALUE_ERROR);
        }
        return $info[$key];
    }

    /**
     * 根据主键获取值
     * @param string $id ID
     * @return mixed  返回值
     */
    final public function get($id = null)
    {
        if ($id !== null) {
            $this->where($this->pk, '=', $id);
            $this->pkval = $id;
        }

        return $this->_get();
    }

    /**
     * 根据查询条件返回数组
     * @return array
     */
    final public function getAll()
    {
        return $this->_getAll();
    }

    //统计查询

    private function _maxMinSumAvg($method, $field)
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        $field = $this->formatColumn($field);
        $this->_sql['select'] = [$method . "(" . $field . ") as info"];
        //buildSql会重置条件，先快照绑定参数
        $params = $this->_bindParams;
        $sql = $this->limit(1)->buildSql();
        $this->sql = $sql;
        /**
         * 仅仅获得sql
         */
        if ($this->justSql) {
            return 0;
        }
        $info = $this->DB->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        $this->lastQueryAt = time();
        return $info['info'] ?? 0;
    }

    /**
     * 查询指定字段的最大值
     * @param string $field 字段名
     * @return number/string 返回最大值
     */
    final public function max($field)
    {
        return $this->_maxMinSumAvg('max', $field);
    }

    /**
     * 查询指定字段的最小值
     * @param string $field 字段名
     * @return number/string 返回最小值
     */
    final public function min($field)
    {
        return $this->_maxMinSumAvg('min', $field);
    }

    /**
     * 查询指定字段的平均值
     * @param string $field 字段名
     * @return number/string 返回平均值
     */
    final public function avg($field)
    {
        return $this->_maxMinSumAvg('avg', $field);
    }

    /**
     * 查询指定字段的和值
     * @param string $field 字段名
     * @return number/string 返回和值
     */
    final public function sum($field)
    {
        return $this->_maxMinSumAvg('sum', $field);
    }

    /**
     * 直接按照分页查询相关的信息，包括总页数以及当前分页的内容
     * @param integer $page 当前要查询的页码
     * @param integer $num 每页的信息条数 默认10条
     * @return array/string   $info 返回值，第一个元素是包含的信息，第二个元素是总的行数
     */
    final public function page($page = 1, $num = 10)
    {
        $page = max($page, 1);
        $start = ($page - 1) * $num;
        $this->limit($num, $start);
        $bak = $this->_sql;
        $bakParams = $this->_bindParams;
        $infos = $this->_getAll();

        $this->_sql = $bak;
        //执行查询后条件被重置了，此处恢复where条件的绑定参数供count使用
        $this->_bindParams = $bakParams;
        $querySql = $this->sql;
        $total = $this->count();
        //重新组装两个SQL
        $this->sql = "Rows:" . $querySql . "\n Count:" . $this->sql;
        return [$infos, $total];
    }

    /**
     * 执行指定的select类型sql
     * @param string $sql sql语句
     * @param int $type 返回类型，默认为关联数组，可以指定其他类型具体查看PDO文档
     * @return array 返回值，多维数组
     * @throws Exception
     */
    final public function fetchAll($sql, $type = \PDO::FETCH_ASSOC)
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        $this->guardSqlFragment($sql);
        $sth = $this->DB->query($sql);
        $this->lastQueryAt = time();
        return $this->DB->fetchAll($type);
    }

    /**
     * 执行指定的select类型sql
     * @param string $sql sql语句
     * @param int $type 返回类型，默认为关联数组，可以指定其他类型具体查看PDO文档
     * @return array 返回值，一维数组
     * @throws Exception
     */
    final public function fetch($sql, $type = \PDO::FETCH_ASSOC)
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        $this->guardSqlFragment($sql);
        $info = $this->DB->query($sql)->fetch($type);
        $this->lastQueryAt = time();
        return $info;
    }

    /**
     * 执行一个没有返回值的sql语句
     * @param string $sql sql语句
     * @return int 执行是否成功
     * @throws Exception
     */
    final public function execute($sql)
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        $this->guardSqlFragment($sql);
        $this->lastQueryAt = time();
        return $this->DB->execute($sql);
    }


    /**
     * 从数据库读取表结构元数据
     * @return array/string
     * @throws Exception
     */
    final public function getMeta()
    {
        static $tableMetas = [];
        $tableName = $this->getTable();

        if (isset($tableMetas[$tableName])) {
            return $tableMetas[$tableName];
        }
        $sql = 'describe ' . $tableName;
        $this->sql = $sql;
        /**
         * 仅仅获得sql
         */
        if ($this->justSql) {
            return [];
        }
        $this->DB->query($sql);
        $this->lastQueryAt = time();
        $rows = $this->DB->fetchAll(\PDO::FETCH_ASSOC);
        $metaData = [];
        foreach ($rows as $row) {
            $metaData[] = [
                'field' => $row['Field'],
                'typeArr' => $this->parseType($row['Type']),
                'type' => $this->parseType($row['Type'])[0],
                'null' => $row['Null'] == 'NO' ? false : true,//是否可以是NULL
                'key' => $row['Key'],
                'default' => $row['Default'],
                'extra' => $row['Extra']
            ];
        }
        $tableMetas[$tableName] = $metaData;
        return $metaData;
    }

    /**
     * 解析类型和数据
     * @param $type
     * @return array
     */
    private function parseType($type)
    {
        $dataType = '';
        $length = 0;
        $data = '';
        $tmp = '';
        if (strpos($type, '(') !== false) {
            for ($i = 0; $i < strlen($type); $i++) {
                $str = $type[$i];
                if ($str == '(') {
                    $dataType = $tmp;
                    $tmp = '';
                    continue;
                }
                if ($str == ')') {
                    $data = $tmp;
                    $tmp = '';
                    continue;
                }
                $tmp .= $str;
            }
        } else {
            $dataType = $type;
        }

        switch ($dataType) {
            case 'set':
                $data = str_replace(['"', "'"], '', $data);
                $data = explode(',', $data);
                break;
            case 'decimal':
                $length = explode(',', $data);
                $data = '';
                break;
            default:
                $length = intval($data);
                $data = '';
                break;
        }

        return [$dataType, $length, $data];
    }


    private function _get()
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        //buildSql会重置条件，先快照绑定参数
        $params = $this->_bindParams;
        $sql = $this->limit(1)->buildSql();
        $this->sql = $sql;
        /**
         * 仅仅获得sql 不实际执行
         */
        if ($this->justSql) {
            return [];
        }
        $info = $this->DB->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        $this->lastQueryAt = time();
        $this->_post = $info;
        $this->isfind = true;
        return $info;
    }

    private function _getAll()
    {
        if (!$this->DB) {
            throw  new Exception('您还没有连接数据库', Exception::CODE_DATABASE_ERROR);
        }
        //buildSql会重置条件，先快照绑定参数
        $params = $this->_bindParams;
        $sql = $this->buildSql();
        $this->sql = $sql;
        /**
         * 仅仅获得sql
         */
        if ($this->justSql) {
            return [];
        }
        $sth = $this->DB->query($sql, $params);
        $this->lastQueryAt = time();
        return $this->DB->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 重置各种查询条件
     * @return void
     */
    protected function resetCon()
    {
        $this->_sql = [
            'where' => [],
            'whereraw' => [],
            'wheretuple' => [],
            'findinset' => [],
            'select' => [],
            'distinct' => [],
            'from' => '',
            'limit' => '',
            'offset' => 0,
            'group' => [],
            'having' => [],
            'union' => [],
            'join' => [],
            'order' => [],
        ];
        $this->_bindParams = [];
        $this->_bindParamSeq = 0;
    }

    public function __destruct()
    {
        $this->DB = null;
        $this->pdo = null;
    }
}
