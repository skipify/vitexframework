<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package Vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
namespace Vitex\Ext;

/**
 * 一个简单的Active record
 * knex.js
 */
class Model
{
    /**
     * 保存各种条件的数组.
     * @var array
     */
    private $_sql = [
        'where'    => [],
        'whereraw' => [],
        'select'   => [],
        'distinct' => [],
        'from'     => '',
        'limit'    => '',
        'offset'   => 0,
        'group'    => [],
        'having'   => [],
        'union'    => [],
        'join'     => [],
        'order'    => [],
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
    private $pdo, $DB;
    private $setfromtable = false;

    public function __construct($table = '')
    {
        $this->vitex = \Vitex\Vitex::getInstance();
        try {
            $this->DB  = $this->vitex->pdo;
            $this->pdo = $this->DB->pdo;
        } catch (Exception $e) {
            throw new Error('使用ORM之前您必须要调用一个数据库连接的类返回一个PDO的变量,或者直接加载pdo中间件');
        }
        if ($table) {
            $this->from($table);
        }
        $class       = explode('\\', get_class($this));
        $this->table = strtolower(end($class));
    }

    /**
     * 定义一个新的模型数据
     * @param array $arr 模型数据
     */
    public function def($arr = [])
    {
        $this->_post = $arr;
        return $this;
    }

    /**
     * 处理映射字段
     * @param string $key 键值
     */
    public function __get($key)
    {
        return isset($this->_post[$key]) ? $this->_post[$key] : null;
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
            } catch (Exception $e) {}
        }
        unset($this->_post[$key]);
    }

    /**
     * 负责执行一些未定义的内容
     * @param  string $method                    方法名
     * @param  array  $args                      数组
     * @return mixed  执行结果或者本身
     */
    public function __call($method, $args)
    {
        $join = ['innerJoin' => 'inner join', 'leftJoin' => 'left join', 'leftOuterJoin' => 'left outer join', 'rightJoin' => 'right join', 'rightOuterJoin' => 'right outer join', 'outerJoin' => 'outer join', 'fullOuterJoin' => 'full outer join', 'crossJoin' => 'cross join'];
        //一堆join操作
        if (isset($join[$method])) {
            $type = $join[$method];
            array_unshift($args, $type);
            call_user_func_array([$this, 'join'], $args);
            return $this;
        }
        //where操作
        if (strpos($method, 'where') !== false || strpos($method, 'Where') !== false) {
            array_unshift($args, $method);
            call_user_func_array([$this, '_where'], $args);
            return $this;
        }

        //getby
        if (substr($method, 0, 5) == 'getBy') {
            $field = str_replace('getBy', '', $method);
            $val   = array_shift($args);
            $this->where($field, '=', $val);
            return $this->_get();
        }
        //getAllBy
        //
        if (substr($method, 0, 8) == 'getAllBy') {
            $field = str_replace('getAllBy', '', $method);
            $val   = array_shift($args);
            $this->where($field, '=', $val);
            return $this->_getAll();
        }
    }

    /**
     * 设置表前缀
     * @param  string $prefix 前缀
     * @return object $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 返回当前对象的实例，一般用于子查询实例化model
     * @return object $this
     */
    public static function sub()
    {
        return new self;
    }

    /**
     * 直接执行sql语句 @#_ 当做表前缀替换掉
     * @param  string $sql           sql语句
     * @return mixed  执行结果
     */
    public function query($sql)
    {
        $sql = str_replace('@#_', $this->prefix, $sql);
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
     * @param  mixed  $column 可以是字符串，多个字段用,分开，也可以是数组每个元素为一个字段，也可以是*
     * @return object $this
     */
    public function select($column = '*')
    {
        if ($column == '*') {
            $this->_sql['select'][] = $column;
            return $this;
        }
        //处理字段名
        if (!is_array($column)) {
            $column = explode(',', $column);
        }
        $column               = array_map([$this, 'formatColumn'], $column);
        $this->_sql['select'] = array_merge($this->_sql['select'], $column);
        return $this;
    }

    /**
     * 转义格式化字段名 主要用 `包括
     * table.field
     * table.*
     * table.field as _field
     * table.field _field
     * @param  string $column           字段名
     * @return string 新的字段名
     */
    private function formatColumn($column)
    {
        $column = trim($column);
        if (strpos($column, '.') !== false) {
            list($table, $column) = explode('.', $column);
            $table                = '`' . $table . '`.';
        } else {
            $table = '';
        }
        if ($column == '*') {
            return $table . $column;
        }
        if (strpos($column, ' ') === false) {
            return $table . '`' . $column . '`';
        }
        $column  = preg_replace('/[ ]+/', ' ', $column);
        $columns = explode(' ', $column);
        if (count($columns) == 3) {
            list($column, $as, $alias) = $columns;
        } else {
            list($column, $alias) = $columns;
        }
        return $table . '`' . $column . '` as `' . $alias . '`';
    }

    /**
     * where查询语句，支持子查询等
     * @param  string                $key    键值
     * @param  string                $op     操作符
     * @param  string/array/callable $val    值
     * @return object                $this
     */
    private function _where($method, $key, $op = '', $val = '')
    {
        $where = ['where' => '', 'whereIn' => 'in', 'whereNotIn' => 'not in', 'whereNull' => 'is', 'whereNotNull' => 'is not', 'whereExists' => 'exists', 'whereNotExists' => 'not exists', 'whereBetween' => 'between', 'whereNotBetween' => 'not between', 'orWhere' => '', 'orWhereIn' => 'in', 'orWhereNotIn' => 'not in', 'orWhereNull' => 'is', 'orWhereNotNull' => 'is not', 'orWhereExists' => 'exists', 'orWhereNotExists' => 'not exists', 'orWhereBetween' => 'between', 'orWhereNotBetween' => 'not between'];

        if (!isset($where[$method])) {
            throw new Error('找不到您要执行的方法' . $method);
        }
        if (strpos($method, 'xists') === false) {
            $key = $this->formatColumn($key);
        }
        //非这俩方法时不需要指定操作符
        if ($method != 'where' && $method != 'orWhere') {
            $val = $op;
        }
        //设定操作符和连接符
        $type = strpos($method, 'or') !== false ? ' or ' : ' and ';
        $_op  = $where[$method];
        $op   = $_op ? $_op : $op;
        if ($op == 'is' || $op == 'is not') {
            $val = 'null';
        }
        $val = is_array($val) ? $val : (string) $val;

        $this->_sql['where'][] = [$key, $op, $val, $type];
        return $this;
    }

    /**
     * 字符串形式的查询语句
     * @param  string $val    查询语句
     * @return obj    $this
     */
    public function whereRaw($val)
    {
        $this->_sql['whereraw'][] = $val;
        return $this;
    }

    /**
     * Having分组操作条件
     * @param  string         $key    键值
     * @param  string         $op     操作符
     * @param  array/callable $val    操作值
     * @param  string         $type   类型 and/or
     * @return object         $this
     */
    public function having($key, $op, $val, $type = "AND")
    {
        $key                    = $this->formatColumn($key);
        $this->_sql['having'][] = [$key, $op, $val, $type];
        return $this;
    }

    /**
     * 要查询的表名
     * @param  string $table  表名
     * @return object $this
     */
    public function from($table)
    {
        $table              = (string) $table;
        $this->_sql['from'] = $table;
        return $this;
    }

    /**
     * 获取当前要查询的表名
     * @return string name
     */
    public function getTable()
    {
        if ($this->_sql['from']) {
            $table = $this->_sql['from'];
        } else {
            $table = $this->table;
        }

        $table = $this->prefix . $table;
        return $this->formatTable($table);
    }

    //提取出表名中的别名
    private function formatTable($table)
    {
        $alias = '';
        if (strpos($table, ' ') !== false) {
            list($table, $alias) = explode(' ', $table);
            $alias               = ' as `' . $alias . '`';
        }
        return '`' . $table . '`' . $alias;
    }

    /**
     * 查询的条数
     * @param  string  $limit  要查询的条数
     * @param  integer $offset 偏移值 默认0
     * @return object  $this
     */
    public function limit($limit, $offset = 0)
    {
        $this->_sql['limit'] = $limit;
        $this->offset($offset);
        return $this;
    }

    /**
     * 单独设置的偏移数制
     * @param  integer $offset 偏移数值
     * @return object  $this
     */
    public function offset($offset)
    {
        $this->_sql['offset'] = $offset;
        return $this;
    }

    /**
     * 排序字段以及排序方式
     * @param  string $column 字段
     * @param  string $way    排序方式
     * @return object $this
     */
    public function orderBy($column, $way = "DESC")
    {
        $column                = $this->formatColumn($column);
        $this->_sql['order'][] = [$column, $way];
        return $this;
    }

    /**
     * group分组操作
     * @param  string $column 要分组的字段
     * @return object $this
     */
    public function groupBy($column)
    {
        $column                = $this->formatColumn($column);
        $this->_sql['group'][] = $column;
        return $this;
    }

    /**
     * 去重查询
     * @param  string/array $column 字段名
     * @return object       $this
     */
    public function distinct($column)
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
     * @param  string $type   各种不同的join操作
     * @param  string $table  join的表明
     * @param  string $col    第一个字段
     * @param  string $col2   第二个字段
     * @return object $this
     */
    private function join($type, $table, $col, $op, $col2 = '')
    {
        $col  = $this->formatColumn($col);
        $col2 = $this->formatColumn($col2);

        $this->_sql['join'][$type][] = [$table, $col, $op, $col2];
        return $this;
    }

    /**
     * union操作
     * @param  string/callable $str    union字符串或者一个可以tostring的对象
     * @return object          $this
     */
    public function union($str)
    {
        $str                   = (string) $str;
        $this->_sql['union'][] = $str;
        return $this;
    }

    /**
     * 设置数据
     * @param  string/array $key    键值
     * @param  string       $val    值
     * @return object       $this
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
     * 构建sql语句
     * @return string
     */
    private function buildSql($iscount = false)
    {
        if (!$this->getTable()) {
            throw new Error('您还没有指定要查询的表名');
        }
        $sql = "select ";

        if ($iscount) {
            $field = is_bool($iscount) ? '*' : $iscount;
            $field = $this->formatColumn($field);
            $sql .= 'count(' . $field . ') as num ';
        } else {
            if ($this->_sql['distinct']) {
                $sql .= 'distinct ' . implode(',', $this->_sql['distinct']) . ' ';
            }

            if (!$this->_sql['distinct']) {
                $field = $this->_sql['select'] ? implode(',', $this->_sql['select']) : '*';
                $sql .= $field . ' ';
            }
        }

        $sql .= " from " . $this->getTable() . ' ';

        $sql .= $this->buildWhere();
        //groupby
        if ($this->_sql['group']) {
            $groupby = array_map(function ($item) {return $this->formatColumn($item);}, $this->_sql['group']);
            $sql .= ' group by ' . implode(',', $groupby) . ' ';
        }

        //having
        if ($this->_sql['having']) {
            $sql .= ' having ';
            foreach ($this->_sql['having'] as $k => list($key, $op, $val, $type)) {
                if ($k != 0) {
                    $sql .= $type . ' ';
                }
                $sql .= ' ' . $key . ' ' . $op . ' ' . $val;
            }
        }

        //union
        if ($this->_sql['union']) {
            foreach ($this->_sql['union'] as $union) {
                $sql .= ' union (' . $union . ') ';
            }
        }
        //orderby
        if ($this->_sql['order']) {
            $sql .= ' order by ';
            $_order = [];
            foreach ($this->_sql['order'] as list($column, $way)) {
                $_order[] = $column . ' ' . $way;
            }
            $sql .= implode(',', $_order);
        }
        //limit
        if ($this->_sql['limit'] && !$iscount) {
            $sql .= ' limit ' . $this->_sql['offset'] . ',' . $this->_sql['limit'];
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
                foreach ($join as list($table, $con1, $op, $con2)) {
                    $table = $this->prefix . $table;
                    $table = $this->formatTable($table);
                    $sql .= $key . ' ' . $table . ' on ' . $con1 . ' ' . $op . ' ' . $con2 . ' ';
                }
            }
        }
        //where
        if ($this->_sql['where'] || $this->_sql['whereraw']) {
            $sql .= 'where ';
            foreach ($this->_sql['where'] as $k => list($column, $op, $val, $type)) {
                if ($k !== 0) {
                    $sql .= $type . ' ';
                }
                switch ($op) {
                    case 'in':
                    case 'not in':
                        if (!is_array($val) && strpos(',', $val) !== false) {
                            $val = explode(',', $val);
                        }
                        if (is_array($val)) {
                            $val = array_map(function ($v) {
                                if (is_numeric($v)) {
                                    return $v;
                                } else {
                                    return "'" . $v . "'";
                                }
                            }, $val);
                            $val = implode(',', $val);
                        }
                        $sql .= $column . ' ' . $op . ' (' . $val . ') ';
                        break;
                    case 'exists':
                    case 'not exists':
                        $sql .= ' ' . $op . "(" . $column . ') ';
                        break;
                    default:
                        if (!is_numeric($val)) {
                            $val = "'" . $val . "'";
                        }
                        $sql .= $column . ' ' . $op . ' ' . $val . ' ';
                }
            }
            //where raw
            foreach ($this->_sql['whereraw'] as $raw) {
                $sql .= ' ' . $raw . ' ';
            }
        }
        return $sql;
    }

    /**
     * 根据where条件修改内容
     * @param  array $arr           要修改的数据 关联数组
     * @return mixed 执行结果
     */
    public function update(array $arr)
    {
        //修改
        $sql  = "update " . $this->getTable() . " set ";
        $sets = [];
        foreach ($arr as $key => $val) {
            $sets[] = $this->formatColumn($key) . ' = :' . $key;
        }
        $sql .= implode(',', $sets);
        $sql .= $this->buildWhere();
        $sth = $this->pdo->prepare($sql);
        return $sth->execute($arr);
    }

    /**
     * 要插入数据库的数据，可以是多维数组
     * 当为二维数组的时候插入多条数据
     * @param  array $arr                               关联数组或者二维数组
     * @return mixed 成功则返回最后插入的ID
     */
    public function insert($arr = [])
    {
        $keys = [];
        if (count($arr) === 0) {
            return false;
        }
        $temp = $arr;
        $ele  = array_pop($temp);
        if (is_array($ele)) {
            $keys = array_keys($ele);
        } else {
            $keys = array_keys($arr);
            $arr  = [$arr];
        }
        //整理keys
        $params = array_map(function ($item) {
            return ':' . $item;
        }, $keys);
        $keys = array_map(function ($item) {
            return $this->formatColumn($item);
        }, $keys);

        $sql    = "insert into " . $this->getTable() . " (" . implode(',', $keys) . ") values (" . implode(',', $params) . ")";
        $sth    = $this->pdo->prepare($sql);
        $lastid = null;
        $this->pdo->beginTransaction();
        foreach ($arr as $val) {
            $sth->execute($val);
            $lastid = $this->pdo->lastInsertId();
        }
        $this->pdo->commit();
        return $lastid;
    }

    /**
     * ORM似的保存
     * 保存当前模型，如果存在主键则尝试修改，如果不存在主键则尝试新建
     * @param  string $id            主键的值
     * @return mixed  执行结果
     */
    public function save($id = '')
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
    }

    /**
     * 删除数据
     * @return boolea 删除的数据结果
     */
    public function delete()
    {
        $sql   = "delete from " . $this->getTable() . " ";
        $where = $this->buildWhere();
        //条件判断
        //没有外部条件的时候查询当前对象是否有查询过的模型
        if (!$where && $this->isfind) {
            $this->where($this->pk, '=', $this->pkval);
            $where = $this->buildWhere();
        }
        if (!$where) {
            throw new Error('删除全部数据请使用truncate方法');
        }
        $sql .= $where;
        return $this->DB->execute($sql);
    }

    /**
     * 清空当前指定的表
     * @return object $this
     */
    public function truncate()
    {
        $table = $this->getTable();
        $sql   = "truncate table " . $table;
        return $this->DB->execute($sql);
    }

    /**
     * 自增一个字段
     * @param  string  $column              字段名
     * @param  integer $amount              自增的数制默认为1
     * @return bool    执行sql的结果
     */
    public function increment($column, $amount = 1)
    {
        $column = $this->formatColumn($column);
        $sql    = "update " . $this->getTable() . " set " . $column . " = (" . $column . " + " . $amount . ") ";
        $sql .= $this->buildWhere();
        return $this->DB->execute($sql);
    }

    /**
     * 自减一个字段
     * @param  string  $column              字段名
     * @param  integer $amount              自增的数制默认为1
     * @return bool    执行sql的结果
     */
    public function decrement($column, $amount = 1)
    {
        $column = $this->formatColumn($column);
        $sql    = "update " . $this->getTable() . " set " . $column . " = (" . $column . " - " . $amount . ") ";
        $sql .= $this->buildWhere();
        return $this->DB->execute($sql);
    }

    /**
     * 统计数量
     * @param  string $column  字段名
     * @return int    数量
     */
    public function count($column = '*')
    {
        $sql  = $this->buildSql($column);
        $info = $this->DB->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return isset($info['num']) ? $info['num'] : 0;
    }

    /**
     * 一个简化的array_map操作，可以按照指定的字段返回一个仅包含该字段的数组
     * @param  string $column        字段名
     * @return array  返回数组
     */
    public function pluck($column)
    {
        $infos = $this->getAll();
        $info  = array_map(function ($val) use ($column) {
            return $val[$column];
        }, $infos);
        return $info;
    }

    /**
     * 根据主键获取值
     * @param  string $id         ID
     * @return mixed  返回值
     */
    public function get($id = null)
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
    public function getAll()
    {
        return $this->_getAll();
    }

    /**
     * 直接按照分页查询相关的信息，包括总页数以及当前分页的内容
     * @param  integer page  当前要查询的页码
     * @param  integer $num  每页的信息条数 默认10条
     * @return array   $info 返回值，第一个元素是包含的信息，第二个元素是总的行数
     */
    public function page($page = 1, $num = 10)
    {
        $start = ($page - 1) * $num;
        $this->limit($num, $start);
        $bak        = $this->_sql;
        $infos      = $this->_getAll();
        $this->_sql = $bak;
        $total      = $this->count();
        return [$infos, $total];
    }

    private function _get()
    {
        $sql          = $this->limit(1)->buildSql();
        $info         = $this->DB->query($sql)->fetch(\PDO::FETCH_ASSOC);
        $this->_post  = $info;
        $this->isfind = true;
        return $info;
    }

    private function _getAll()
    {
        $sql = $this->buildSql();
        $sth = $this->DB->query($sql);
        return $this->DB->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 重置各种查询条件
     * @return void
     */
    private function resetCon()
    {
        $this->_sql = [
            'where'    => [],
            'whereraw' => [],
            'select'   => [],
            'distinct' => [],
            'from'     => '',
            'limit'    => '',
            'offset'   => 0,
            'group'    => [],
            'having'   => [],
            'union'    => [],
            'on'       => [],
            'join'     => [],
            'order'    => [],
        ];
    }
}
