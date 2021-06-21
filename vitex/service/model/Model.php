<?php declare(strict_types=1);
/**
 * Vitex 一个基于php8.0开发的 快速开发restful API的微型框架
 * @version  2.0.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex\service\model;

use vitex\core\Exception;
use vitex\service\model\exception\EmptyDataException;
use vitex\service\model\sql\SelectWrapper;
use vitex\service\model\sql\SqlTpl;
use vitex\service\model\sql\SqlUtil;
use vitex\service\model\sql\UpdateWrapper;
use vitex\service\model\sql\Wrapper;

/**
 * 一个简单的Active record
 * knex.js
 * @method array getBy*(string $column, String $val)
 * @method array getAllBy*(string $column, String $val)
 */
class Model
{

    public const SLAVER = 'slaver';

    public const MASTER = 'master';
    /**
     * 保存各种条件的数组.
     * @var array
     */
    private $_sql = [
        'wheretuple' => [],
        'select' => [],
        'from' => '',
        'offset' => 0,
        'union' => [],
        'join' => [],
    ];
    /**
     * 保存数据集合的数组
     * @var array
     */
    private $_post = [];

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
     * 数据库的连接池
     * @var array
     */
    protected $connectPool = [];

    /**
     * 当前的数据库链接
     * @var \vitex\service\model\Pdo
     */
    protected $currentConnect = null;

    /**
     * 仅仅获取sql，不获取内容
     * @var bool
     */
    private $justSql = false;

    /**
     * 上一次检索时间
     * @var
     */
    public $lastQueryAt;

    /**
     * Sql查询包装器
     * @var SelectWrapper
     */
    private SelectWrapper $selectWrapper;

    private UpdateWrapper $updateWrapper;

    /**
     * @var $DB PDO
     */
    public $DB;

    /**
     * 是否是构造的子查询
     * @var bool
     */
    private bool $isSub = false;

    public function __construct($table = '')
    {
        if ($table) {
            $this->table = $table;
        } else {
            $class = explode('\\', get_class($this));
            $this->table = strtolower(end($class));
        }
        $this->selectWrapper = new SelectWrapper();
        $this->updateWrapper = new UpdateWrapper();
    }

    /**
     * 初始化数据库连接
     * @param array $setting
     * @return Model
     */
    public function init(array $setting)
    {
        $this->connectPool['default'] = PdoUtil::instance()->getByConfig($setting);
        $this->currentConnect = $this->connectPool['default'];
        $this->currentConnect = $this->connectPool['default'];
        //兼容老版本
        $this->DB = $this->currentConnect;
        $this->pdo = $this->currentConnect->pdo;
        return $this;
    }

    /**
     * 切换Model层使用的数据库连接
     * @param array | string $setting 数据库链接信息 或者一个别名
     * @param string $alias 别名
     * @return self
     */
    public function changeDatabase(array|string $setting, $alias = '')
    {
        $alias = $alias ? $alias : (is_string($setting) ? $setting : md5(serialize($setting)));

        if (isset($this->connectPool[$alias])) {
            return $this;
        }
        if (is_string($setting)) {
            $this->connectPool[$alias] = PdoUtil::instance()->getByConfigKey($setting);
        } else {
            $this->connectPool[$alias] = PdoUtil::instance()->getByConfig($setting);
        }
        $this->currentConnect = $this->connectPool[$alias];
        return $this;
    }

    /**
     * 获得当前链接
     * @return model|Pdo|null
     */
    public function getConnect(string $key = '')
    {
        if ($this->currentConnect) {
            return $this->currentConnect;
        }
        if (empty($key)) {
            throw new Exception("Not Specify a database config `key`");
        }
        if (isset($this->connectPool[$key])) {
            return $this->connectPool[$key];
        }
        return PdoUtil::instance()->getByConfigKey($key);
    }


    /**
     * @return string
     */
    public function getPk(): string
    {
        return $this->pk;
    }

    /**
     * @param string $pk
     */
    public function setPk(string $pk): void
    {
        $this->pk = $pk;
    }

    /**
     * @param string $table
     */
    public function setTable(mixed $table): void
    {
        $this->table = $table;
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
        $this->_post[$key] = $val;
    }

    public function __isset($key)
    {
        return isset($this->_post[$key]);
    }

    public function __unset($key)
    {
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
            $infos = $this->_getAll();
            $this->resetCon();
            return $infos;
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
        $self->selectWrapper = new SelectWrapper();
        $self->isSub = true;
        return $self;
    }

    /**
     * 直接执行sql语句 @#_ 当做表前缀替换掉
     * @param string $sql sql语句
     * @return mixed 执行结果
     * @throws Exception
     */
    public function query($sql, array $data = [])
    {
        $sql = str_replace('@#_', $this->prefix, $sql);
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        $this->lastQueryAt = time();
        return $this->getConnect(self::MASTER)->query($sql, $data);
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
        $this->selectWrapper->select($column);
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
        return SqlUtil::wrapSelectColumn($column);
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
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->where($k, '=', $v);
            }
            return $this;
        }

        switch ($op) {
            case '=':
                $this->selectWrapper->eq($key, $val);
                break;
            case '!=':
                $this->selectWrapper->ne($key, $val);
                break;
            case 'like':
                $this->selectWrapper->like($key, $val);
                break;
            case '>':
                $this->selectWrapper->gt($key, $val);
                break;
            case '<':
                $this->selectWrapper->lt($key, $val);
                break;
            case '>=':
                $this->selectWrapper->ge($key, $val);
                break;
            case '<=':
                $this->selectWrapper->le($key, $val);
                break;

            default:
                throw new \InvalidArgumentException('不支持的操作类型');
        }

        return $this;
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
        $this->selectWrapper->or();
        $this->where($key, $op, $val);
        $this->selectWrapper->and();
        return $this;
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
        $this->selectWrapper->in($key, $val);
        return $this;
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
        $where = $obj->buildWhere();
        $mode = $this->selectWrapper->getMode();
        $this->selectWrapper->and();
        $this->selectWrapper->sql('(' . $where . ')');
        if ($mode == "or") {
            $this->selectWrapper->or();
        }
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
        $where = $obj->buildWhere();
        $mode = $this->selectWrapper->getMode();
        $this->selectWrapper->or();
        $this->selectWrapper->sql('(' . $where . ')');
        if ($mode == "and") {
            $this->selectWrapper->and();
        }
        return $this;
    }


    /**
     * 基本的whereNotIn查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param mixed $val 值
     * @return Model
     * @throws Exception
     * @throws \Error
     */
    public function whereNotIn($key, $val)
    {
        if (!$val) {
            throw new Exception("orWhereIn方法的第二个参数不得为空", Exception::CODE_PARAM_VALUE_ERROR);
        }
        $this->selectWrapper->notIn($key, $val);
        return $this;
    }

    /**
     * 基本的or whereIn查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @param mixed $val 值
     * @return self
     */
    public function orWhereIn($key, $val)
    {
        $this->selectWrapper->or();
        $this->whereIn($key, $val);
        $this->selectWrapper->and();
        return $this;
    }

    /**
     * 基本的or whereNotIn查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @param mixed $val 值
     * @return self
     */
    public function orWhereNotIn($key, $val)
    {
        $this->selectWrapper->or();
        $this->whereNotIn($key, $val);
        $this->selectWrapper->and();
        return $this;
    }

    /**
     * 基本的where is null查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @return self
     */
    public function whereNull($key)
    {
        $this->selectWrapper->isNull($key);
        return $this;
    }

    /**
     * 基本的where is not null查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @return self
     */
    public function whereNotNull($key)
    {
        $this->selectWrapper->notNull($key);
        return $this;
    }

    /**
     * 基本的or where is null查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @return self
     */
    public function orWhereNull($key)
    {
        $this->selectWrapper->or()->isNull($key)->and();
        return $this;
    }

    /**
     * 基本的or where is not null查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @return self
     */
    public function orWhereNotNull($key)
    {
        $this->selectWrapper->or()->notNull($key)->and();
        return $this;
    }

    /**
     * 基本的where exists(select name form user where id=1)查询条件,与前面的操作使用and连接
     * @param string $key 条件列名 没啥用途
     * @param string $val 值,如说明,不要包含最外层的 ()
     * @return self
     */
    public function whereExists($key, $val)
    {
        $this->selectWrapper->exists($val);
        return $this;
    }

    /**
     * 基本的where not exists(select name form user where id=1)查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param string $val 值,如说明,不要包含最外层的 ()
     * @return self
     */
    public function whereNotExists($key, $val)
    {
        $this->selectWrapper->notExists($val);
        return $this;
    }

    /**
     * 基本的 or where exists(select name form user where id=1)查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param string $val 值,如说明,不要包含最外层的 ()
     * @return self
     */
    public function orWhereExists($key, $val)
    {
        $this->selectWrapper->or()->exists($val)->and();
        return $this;
    }

    /**
     * 基本的 or where not exists(select name form user where id=1)查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param string $val 值,如说明,不要包含最外层的 ()
     * @return self
     */
    public function orWhereNotExists($key, $val)
    {
        $this->selectWrapper->or()->notExists($val)->and();
        return $this;
    }

    /**
     * 基本的where between a and b查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param array $val ,这是一个数组,包含两个元素 between $val[0] and $val[1]
     * @return self
     */
    public function whereBetween($key, array $val)
    {
        $this->selectWrapper->between($key, $val[0], $val[1]);
        return $this;
    }

    /**
     * 基本的where not between a and b查询条件,与前面的操作使用and连接
     * @param string $key 条件列名
     * @param array $val ,这是一个数组,包含两个元素 not between $val[0] and $val[1]
     * @return self
     */
    public function whereNotBetween($key, array $val)
    {
        $this->selectWrapper->notBetween($key, $val[0], $val[1]);
        return $this;
    }

    /**
     * 基本的or where between a and b查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @param array $val ,这是一个数组,包含两个元素 between $val[0] and $val[1]
     * @return self
     */
    public function orWhereBetween($key, array $val)
    {
        $this->selectWrapper->or()->between($key, $val[0], $val[1])->and();
        return $this;
    }

    /**
     * 基本的or where not between a and b查询条件,与前面的操作使用or连接
     * @param string $key 条件列名
     * @param array $val ,这是一个数组,包含两个元素 not between $val[0] and $val[1]
     * @return self
     */
    public function orWhereNotBetween($key, array $val)
    {
        $this->selectWrapper->or()->notBetween($key, $val[0], $val[1])->and();
        return $this;
    }

    /**
     * 字符串形式的查询语句
     * and xxxx
     * or xxxxx
     * @param string $sql 查询语句
     * @return self
     */
    public function whereRaw($sql)
    {
        // 因为新的拼接不需要提前写 and/or  此处是兼容之前写法
        $sql = trim($sql);
        $mode = $this->selectWrapper->getMode();
        if (strtolower(substr($sql, 0, 3)) == 'and') {
            $sql = substr($sql, 3);
            $this->selectWrapper->and();
            $this->selectWrapper->sql($sql);
        }

        if (strtolower(substr($sql, 0, 2)) == 'or') {
            $sql = substr($sql, 3);
            $this->selectWrapper->or();
            $this->selectWrapper->sql($sql);
        }
        //还原原来的链接模式
        if ($mode == 'and') {
            $this->selectWrapper->and();
        } else {
            $this->selectWrapper->or();
        }

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
        if ($type == 'and') {
            $this->selectWrapper->findInSet($column, $val);
        } else {
            $this->selectWrapper->or()->findInSet($column, $val)->and();
        }
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
        $key = $this->formatColumn($key);
        $this->selectWrapper->having("$key $op $val");
        return $this;
    }

    /**
     * 要查询的表名
     * @param string $table 表名
     * @return self
     */
    final public function from($table)
    {
        $table = (string)$table;
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

        /**
         * 处理子查询问题
         * 子查询使用()包裹
         */
        if (strpos($table, ')') !== false && strpos($table, '(') !== false) {
            return $table;
        }
        $table = $this->prefix . $table;
        return SqlUtil::wrapSelectColumn($table);
    }

    /**
     * 返回From设置的表名
     * @return string
     */
    public function getFromTable()
    {
        $table = $this->_sql['from'];
        if ($table) {
            return SqlUtil::wrapSelectColumn($table);
        }
        return '';
    }

    /**
     * 查询的条数
     * @param int $limit 要查询的条数
     * @param integer $offset 偏移值 默认0
     * @return self
     */
    final public function limit($limit, $offset = 0)
    {
        if ($limit > PHP_INT_MAX) {
            throw new \InvalidArgumentException("limit Is Too Max");
        }

        $this->selectWrapper->limit((int)$limit, (int)$offset);
        return $this;
    }

    /**
     * 单独设置的偏移数制
     * @param integer $offset 偏移数值
     * @return self
     * @deprecated
     */
    final public function offset($offset)
    {
        if ($offset >= PHP_INT_MAX) {
            throw new \InvalidArgumentException("offset Is Too Max");
        }
        //$this->_sql['offset'] = $offset;
        return $this;
    }

    /**
     * 排序字段以及排序方式
     * @param string $column 字段
     * @param string $way 排序方式
     * @return self
     */
    final public function orderBy($column, $way = "DESC")
    {
        $column = $this->formatColumn($column);
        $this->selectWrapper->orderBy($column, $way);
        return $this;
    }

    /**
     * group分组操作
     * @param string $column 要分组的字段
     * @return self
     */
    final public function groupBy($column)
    {
        $column = $this->formatColumn($column);
        $this->selectWrapper->groupBy($column);
        return $this;
    }

    /**
     * 去重查询
     * @param string /array $column 字段名
     * @return self
     */
    final public function distinct($column)
    {
        $this->selectWrapper->distinct($column);
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
        $col = $this->formatColumn($col);
        $col2 = $this->formatColumn($col2);
        $this->_sql['join'][$type][] = [$table, $col, $op, $col2];
        return $this;
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
        $str = (string)$str;
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
        $this->updateWrapper->set($key, $val);
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
     * @deprecated
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
            $sql .= $this->selectWrapper->count($field);
            $this->selectWrapper->setBuildType(Wrapper::BUILD_TYPE_COUNT);
        } else {
            $sql .= $this->selectWrapper->toSql();
        }

        $sql .= " from " . $this->getTable() . ' ';
        //union
        if ($this->_sql['union']) {
            foreach ($this->_sql['union'] as $union) {
                $sql .= ' union (' . $union . ') ';
            }
        }
        $sql .= $this->buildWhere();
        $this->selectWrapper->setBuildType(Wrapper::BUILD_TYPE_COMMON);
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
                    $table = SqlUtil::wrapSelectColumn($table);;
                    $sql .= sprintf(' %s %s on %s %s %s', $key, $table, $con1, $op, $con2);
                }
            }
        }
        $sql .= $this->selectWrapper->build();
        /**
         * 如果是子查询的话  替换占位可能会重复
         * 目前没有想到好的方式传递
         * 因此这里先替换拼接
         */
        if ($this->isSub) {
            foreach ($this->selectWrapper->dataHolder->getData() as $key => $val) {
                $sql = str_replace(':' . $key, is_numeric($val) ? $val : "'" . $val . "'", $sql);
            }
        }

        foreach ($this->_sql['wheretuple'] as list($_sql, $mode)) {
            $sql .= ' ' . $mode . ' (' . $_sql . ' ) ';
        }
        return $sql;
    }

    /**
     * 构造一个sql语句
     * @return string
     */
    public function build()
    {
        return $this->buildSql();
    }

    /**
     * 根据where条件修改内容
     * @param array $arr 要修改的数据 关联数组
     * @return mixed 执行结果
     * @throws Exception
     */
    final public function update(array $arr)
    {
        foreach ($arr as $key => $val) {
            $this->updateWrapper->set($key, $val);
        }

        //修改
        $sql = sprintf(SqlTpl::UPDATE, $this->getTable(), $this->updateWrapper->toSql(), $this->buildWhere());
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        //todo 俩值重复的时候有问题
        $data = array_merge($this->updateWrapper->dataHolder->getData(), $this->selectWrapper->dataHolder->getData());
        $ret = $this->getConnect(self::MASTER)->execute($sql, $data);

        $this->resetCon();
        return $ret;
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
        $keys = array_map(function ($item) {
            return $this->formatColumn($item);
        }, $keys);

        $sql = sprintf(SqlTpl::INSERT, $this->getTable(), implode(',', $keys), implode(',', $params));
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        $lastid = null;
        $pdo = $this->getConnect(self::MASTER);
        $sth = $pdo->pdo->prepare($sql);
        !$this->_begintransaction && count($arr) > 1 && $this->begin();
        foreach ($arr as $val) {
            $sth->execute($val);
            $lastid = $pdo->pdo->lastInsertId();
        }
        $this->_begintransaction && count($arr) > 1 && $this->commit();
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
        if ($this->_begintransaction) {
            throw new Exception("已经开启了一个事务，请勿重新开启", Exception::CODE_DATABASE_ERROR);
        }
        $this->getConnect(self::MASTER)->pdo->beginTransaction();
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
        $this->_begintransaction = false;
        return $this->getConnect(self::MASTER)->pdo->commit();
    }

    /**
     * 回滚事务
     * @return $this
     * @throws Exception
     */
    final public function rollBack()
    {
        if ($this->_begintransaction) {
            $this->getConnect(self::MASTER)->pdo->rollBack();
            $this->_begintransaction = false;
        }
        return $this;
    }

    /**
     * ORM似的保存
     * 保存当前模型，如果存在主键则尝试修改，如果不存在主键则尝试新建
     * @param string $id 主键的值
     * @return mixed  执行结果
     * @deprecated
     */
    final public function save($id = '')
    {
        $pkval = $id ?: $this->pkval;
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
        $where = $this->buildWhere();
        if (!$where) {
            throw new \Error('删除全部数据请使用truncate方法', Exception::CODE_DATABASE_ERROR);
        }
        $sql = sprintf(SqlTpl::DELETE, $this->getTable(), $where);
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        $ret = $this->getConnect(self::MASTER)->execute($sql, $this->selectWrapper->dataHolder->getData());
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
        $table = $this->getTable();
        $sql = sprintf(SqlTpl::TRUNCATE, $table);
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            return true;
        }
        return $this->getConnect(self::MASTER)->execute($sql);
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
        return $this->stepField($column, $amount, false);
    }

    /**
     * 自减/增一个字段
     * @param mixed $column 字段名,可以使用一个数组传递多个字段
     * @param mixed $amount 自增的数制默认为1，如果是一个数组则对应前面的字段也必须为数组，如果column为数组此参数不为数组则默认所有字段增加相同的值
     * @param mixed $positive
     * @return bool                    执行sql的结果
     * @throws \vitex\core\Exception
     */
    private function stepField($column, $amount, $positive = true)
    {
        if (is_array($column)) {
            if (is_array($amount) && count($amount) != count($column)) {
                throw new Exception("传递的字段与自增值无法对应，请查看数量", Exception::CODE_PARAM_NUM_ERROR);
            }
        }

        if ($positive) {
            $this->updateWrapper->increment($column, $amount);
        } else {
            $this->updateWrapper->decrement($column, $amount);
        }

        $sql = sprintf(SqlTpl::UPDATE, $this->getTable(), $this->updateWrapper->toSql(), $this->buildWhere());
        $this->sql = $sql;
        /**
         * 只获取sql
         */
        if ($this->justSql) {
            $this->resetCon();
            return true;
        }
        /**
         * 合并 查询和 设置的条件参数
         */
        $data = array_merge($this->updateWrapper->dataHolder->getData(), $this->selectWrapper->dataHolder->getData());
        $ret = $this->getConnect(self::MASTER)->execute($sql, $data);
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
        $sql = $this->buildSql($column);
        $this->sql = $sql;
        /**
         * 仅仅获取sql，不实际执行
         */
        if ($this->justSql) {
            return 0;
        }
        $info = $this->getConnect(self::SLAVER)->query($sql, $this->selectWrapper->dataHolder->getData())->fetch(\PDO::FETCH_ASSOC);
        $this->lastQueryAt = time();
        $this->resetCon();
        return $info['num'] ?? 0;
    }

    /**
     * 一个简化的array_map操作，可以按照指定的字段返回一个仅包含该字段的数组
     * @param string $column 字段名
     * @return array  返回数组
     */
    final public function pluck($column)
    {
        $infos = $this->_getAll();
        $this->resetCon();
        $info = array_map(function ($val) use ($column) {
            return $val[$column];
        }, $infos);
        return $info;
    }

    /**
     * 用于查询返回记录行中的一个字段，仅包含一个字段
     * @param $column string 字段名称 只能为 field 或者为table.field的形式
     * @param null $defaultValue 如果当前条件无法查出信息则返回默认值
     * @return string
     * @throws Exception
     */
    final public function field($column, $defaultValue = null)
    {
        $key = $column;
        if (strpos($column, '.') !== false) {
            list(, $key) = explode('.', $column);
        }
        $key = str_replace('`', '', $key);
        $this->select($column);
        $info = $this->get();
        if (!$info) {
            return $defaultValue;
        }
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
        $infos = $this->_getAll();
        $this->resetCon();
        return $infos;
    }

    //统计查询

    private function _maxMinSumAvg($method, $field)
    {
        $field = $this->formatColumn($field);
        $this->_sql['select'] = [$method . "(" . $field . ") as info"];
        $sql = $this->limit(1)->buildSql();
        $this->sql = $sql;
        /**
         * 仅仅获得sql
         */
        if ($this->justSql) {
            return 0;
        }
        $info = $this->getConnect(self::SLAVER)->query($sql, $this->selectWrapper->dataHolder->getData())->fetch(\PDO::FETCH_ASSOC);
        $this->lastQueryAt = time();
        $this->resetCon();
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
     * @param integer $totalRow 最大条数
     * @return array/string   $info 返回值，第一个元素是包含的信息，第二个元素是总的行数
     */
    final public function page($page = 1, $num = 10, $total = null)
    {
        /**
         * 避免太大的分页和偏移导致的错误
         */
        if ($page < 0 || $page > PHP_INT_MAX || $num < 0 || $num > PHP_INT_MAX || $page * $num > PHP_INT_MAX) {
            throw new \InvalidArgumentException("page or num must between 0 and " . PHP_INT_MAX);
        }
        $page = max($page, 1);
        $start = ($page - 1) * $num;
        $this->limit($num, $start);
        $bak = $this->_sql;
        $infos = $this->_getAll();

        $this->_sql = $bak;
        $querySql = $this->sql;
        /**
         * 如果制定了总条数则不会再查询总条数
         */
        if ($total === null) {
            /**
             * 因为查询一次之后会生成一些数据，此处重复使用之前的条件需要重置一些数据
             */
            $this->selectWrapper->dataHolder->reset();
            $total = $this->count();
        }
        $this->resetCon();
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
        $sth = $this->getConnect(self::SLAVER)->query($sql);
        $this->lastQueryAt = time();
        return $sth->fetchAll($type);
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
        $info = $this->getConnect(self::SLAVER)->query($sql)->fetch($type);
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
        $this->lastQueryAt = time();
        return $this->getConnect(self::MASTER)->execute($sql);
    }


    private function _get()
    {

        $sql = $this->limit(1)->buildSql();
        $this->sql = $sql;
        /**
         * 仅仅获得sql 不实际执行
         */
        if ($this->justSql) {
            return [];
        }
        $info = $this->getConnect(self::SLAVER)->query($sql, $this->selectWrapper->dataHolder->getData())->fetch(\PDO::FETCH_ASSOC);
        $this->lastQueryAt = time();
        $this->_post = $info;
        $this->isfind = true;
        //重置各种条件
        $this->resetCon();
        return $info;
    }

    private function _getAll()
    {
        $sql = $this->buildSql();
        $this->sql = $sql;
        /**
         * 仅仅获得sql
         */
        if ($this->justSql) {
            return [];
        }
        $sth = $this->getConnect(self::SLAVER)->query($sql, $this->selectWrapper->dataHolder->getData());
        $this->lastQueryAt = time();
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 打印调试信息
     * @throws EmptyDataException
     */
    public function debugDumpParams()
    {
        if ($this->currentConnect) {
            $this->currentConnect->debugDumpParams();
        } else {
            throw new EmptyDataException("无法找到信息");
        }
    }

    /**
     * 重置各种查询条件
     * @return void
     */
    protected function resetCon()
    {
        $this->_sql = [
            'wheretuple' => [],
            'select' => [],
            'from' => '',
            'offset' => 0,
            'union' => [],
            'join' => [],
        ];
        $this->updateWrapper = new UpdateWrapper();
        $this->selectWrapper = new SelectWrapper();
    }

    public function __destruct()
    {
    }
}