<?php


namespace vitex\service\model\sql;

/**
 * SQL条件包装器
 * 只有WHERE查询条件
 * $this->gt("id", 1)->between("age", 10, 20)->nested()->eq("sex",1)->gt("grade",80)->endNested()
 * id > 1 and age between 10 and 20 and (sex = 1 and grade > 80)
 *
 * 拼接后的sql 中的查询内容使用占位形式，因此sql里面的内容会被 :val的形式
 * $dataHolder 为替换的数据
 * @package vitex\service\model\sql
 */
class Wrapper
{
    /**
     * 普通拼接
     */
    const BUILD_TYPE_COMMON = "common";

    /**
     * count拼接
     */
    const BUILD_TYPE_COUNT = "count";

    /**
     * 拼接的模式，拼接的时候分为 普通和 Count
     * 普通模式则需要拼接Limit count 则不需要拼接limit
     * @var
     */
    private $buildType = self::BUILD_TYPE_COMMON;

    /**
     * 嵌套子查询的父级查询
     * @var mixed|null
     */
    private $parent;

    /**
     * 在一次查询中保存使用Key的值
     * @var Holder
     */
    public Holder $dataHolder;

    /**
     * 是and链接模式 还是or模式，默认是 and模式
     * @var bool
     */
    private string $mode = 'and';

    private array $data = [
        'and' => [],
        'or' => [],
        'group' => [],
        'order' => [],
        'having' => []
    ];

    /**
     * limit
     * @var string
     */
    private string $limit = '';

    /**
     * Wrapper constructor.
     * @param null | Wrapper $parent
     */
    public function __construct($parent = null)
    {
        if ($parent === null) {
            $this->dataHolder = new Holder();
        } else {
            $this->parent = $parent;
            /**
             * 子查询的占位与主查询保持一致
             */
            $this->dataHolder = $parent->dataHolder;
        }
    }

    /**
     * @param mixed|null $parent
     */
    public function setParent(mixed $parent): void
    {
        $this->parent = $parent;
    }

    /**
     *  =
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function eq(string $column, mixed $val): Wrapper
    {
        if ($val === null) {
            $param = new Param(WrapperConst::IS_NULL, $column);
            $param->setSigle();
        } else {
            $param = new Param(WrapperConst::EQ, $column, $val);
        }
        $this->data[$this->mode][] = $param;
        return $this;
    }

    /**
     * !=
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function ne(string $column, mixed $val): Wrapper
    {
        if ($val === null) {
            $param = new Param(WrapperConst::NOT_NULL, $column);
            $param->setSigle();
        } else {
            $param = new Param(WrapperConst::NE, $column, $val);
        }
        $this->data[$this->mode][] = $param;
        return $this;
    }

    /**
     * >
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function gt(string $column, mixed $val): Wrapper
    {

        $param = new Param(WrapperConst::GT, $column, $val);
        $this->data[$this->mode][] = $param;
        return $this;
    }

    /**
     * >=
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function ge(string $column, mixed $val): Wrapper
    {
        $param = new Param(WrapperConst::GE, $column, $val);
        $this->data[$this->mode][] = $param;

        return $this;
    }

    /**
     * <
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function lt(string $column, mixed $val): Wrapper
    {
        $param = new Param(WrapperConst::LT, $column, $val);
        $this->data[$this->mode][] = $param;

        return $this;
    }

    /**
     * <=
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function le(string $column, mixed $val): Wrapper
    {
        $param = new Param(WrapperConst::LE, $column, $val);
        $this->data[$this->mode][] = $param;

        return $this;
    }

    /**
     * between A and B
     * @param string $column
     * @param mixed $val
     * @param mixed $trailVal
     * @return $this
     */
    public function between(string $column, mixed $val, mixed $trailVal): Wrapper
    {
        $param = new Param(WrapperConst::BETWEEN, $column, $val,$trailVal);
        $this->data[$this->mode][] = $param;

        return $this;
    }

    /**
     * NOT BETWEEN A AND B
     * @param string $column
     * @param mixed $val
     * @param mixed $trailVal
     * @return $this
     */
    public function notBetween(string $column, mixed $val, mixed $trailVal): Wrapper
    {
        $param = new Param(WrapperConst::NOT_BETWEEN, $column, $val);
        $this->data[$this->mode][] = $param;

        return $this;
    }

    /**
     * like  %val%
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function like(string $column, mixed $val): Wrapper
    {
        if (is_string($val)) {
            //处理一下 匹配的 %
            $val = "%" . trim($val, "%") . '%';
        }
        $param = new Param(WrapperConst::LIKE, $column, $val);
        $this->data[$this->mode][] = $param;
        return $this;
    }

    /**
     * not like %val%
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function notLike(string $column, mixed $val): Wrapper
    {
        $param = new Param(WrapperConst::NOT_LIKE, $column, $val);
        $this->data[$this->mode][] = $param;

        return $this;
    }

    /**
     * like %val
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function likeLeft(string $column, mixed $val): Wrapper
    {
        if (is_string($val)) {
            //处理一下 匹配的 %
            $val = "%" . trim($val, "%");
        }
        $param = new Param(WrapperConst::LIKE, $column, $val);
        $this->data[$this->mode][] = $param;

        return $this;
    }

    /**
     * like val%
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function likeRight(string $column, mixed $val): Wrapper
    {
        if (is_string($val)) {
            //处理一下 匹配的 %
            $val = trim($val, "%") . '%';
        }
        $param = new Param(WrapperConst::LIKE, $column, $val);
        $this->data[$this->mode][] = $param;
        return $this;
    }

    /**
     * is  null
     * @param string $column
     * @return $this
     */
    public function isNull(string $column): Wrapper
    {

        $param = new Param(WrapperConst::IS_NULL, $column);
        $param->setSigle();
        $this->data[$this->mode][] = $param;
        return $this;
    }

    /**
     * is not null
     * @param string $column
     * @return $this
     */
    public function notNull(string $column): Wrapper
    {
        $param = new Param(WrapperConst::NOT_NULL, $column);
        $param->setSigle();
        $this->data[$this->mode][] = $param;

        return $this;
    }

    /**
     * in (a,b,c)
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function in(string $column, mixed $val): Wrapper
    {
        if ($val === null) {
            throw new \InvalidArgumentException("where in (param) 参数不得为空");
        }
        /**
         * 如果是一个子查询
         * 字符串子查询按标识符级校验(子查询包含select会被拒绝，请使用Model::sub()对象方式)
         * 子查询对象中的占位参数会重写key后合并到当前查询中，执行时统一使用prepare绑定
         */
        if (is_object($val) || (is_string($val) && SqlUtil::isSubQuery($val))) {
            $subSql = is_object($val) ? $this->buildSubModelSql($val) : SqlUtil::guardIdentifier($val);
            //片段会进入sprintf模板，转义其中的%避免被再次解析
            $tpl = str_replace(':sub', str_replace('%', '%%', $subSql), WrapperConst::SUB_IN);
            $param = new Param($tpl, $column, null);
            $param->setSigle(true);
        } else {
            $param = new Param(WrapperConst::IN, $column, is_string($val) ? explode(',', $val) : $val);
        }
        $this->data[$this->mode][] = $param;

        return $this;
    }

    /**
     * not in (a,b,c)
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function notIn(string $column, mixed $val): Wrapper
    {
        if ($val === null) {
            throw new \InvalidArgumentException("where not in (param) 参数不得为空");
        }
        /*
        * 如果是一个子查询
        * 字符串子查询按标识符级校验(子查询包含select会被拒绝，请使用Model::sub()对象方式)
        * 子查询对象中的占位参数会重写key后合并到当前查询中，执行时统一使用prepare绑定
        */
        if (is_object($val) || (is_string($val) && SqlUtil::isSubQuery($val))) {
            $subSql = is_object($val) ? $this->buildSubModelSql($val) : SqlUtil::guardIdentifier($val);
            //片段会进入sprintf模板，转义其中的%避免被再次解析
            $tpl = str_replace(':sub', str_replace('%', '%%', $subSql), WrapperConst::SUB_NOT_IN);
            $param = new Param($tpl, $column, null);
            $param->setSigle(true);
        } else {
            $param = new Param(WrapperConst::NOT_IN, $column, is_string($val) ? explode(',', $val) : $val);
        }
        $this->data[$this->mode][] = $param;
        return $this;
    }

    /**
     * 构建子查询Model对象的SQL字符串
     * 构建前重置子查询的构建期占位参数(保留合并参数)，
     * 防止复用同一个子查询对象时产生多余的绑定参数；构建后把其中的占位参数合并到当前查询
     * @param mixed $val 子查询Model对象
     * @return string 子查询SQL
     */
    private function buildSubModelSql($val): string
    {
        //重置构建参数，防止复用时残留多余参数
        if (method_exists($val, 'getSelectWrapper')) {
            $val->getSelectWrapper()->dataHolder->reset();
        }
        $sql = $val->build();
        if (method_exists($val, 'getSelectWrapper')) {
            $sql = $this->dataHolder->mergeData($sql, $val->getSelectWrapper()->dataHolder->getData());
        }
        return $sql;
    }

    /**
     * a exists (select id from xx)
     * @param string $val 子查询语句，不要包含最外层的 ()
     * @return $this
     */
    public function exists(string $val): Wrapper
    {
        //子查询片段统一安全校验(拦截注释/分号/危险函数/盲注与编码外泄函数)
        SqlUtil::guardSubQuery($val);
        $this->data[$this->mode][] = 'EXISTS (' . $val . ')';
        return $this;
    }

    /**
     * a not exists (select id from xx)
     * @param string $val 子查询语句，不要包含最外层的 ()
     * @return $this
     */
    public function notExists(string $val): Wrapper
    {
        //子查询片段统一安全校验(拦截注释/分号/危险函数/盲注与编码外泄函数)
        SqlUtil::guardSubQuery($val);
        $this->data[$this->mode][] = 'NOT EXISTS (' . $val . ')';
        return $this;
    }

    /**
     *  find_in_set 参数位置和其他的相反 需要单独处理一下
     * @param string $column
     * @param mixed $val
     * @return $this
     */
    public function findInSet(string $column, mixed $val): Wrapper
    {
        $param = new Param(WrapperConst::FIND_IN_SET, $column, $val);
        $param->setColumnFirst(false);
        $this->data[$this->mode][] = $param;
        return $this;
    }

    /**
     * 填写直接的sql
     * @param $sql
     */
    public function sql($sql)
    {
        //原生片段入口校验(严格模式，与whereRaw一致；whereTuple等内部片段为列名与占位符，不受影响)
        SqlUtil::guardSqlFragment($sql, false);
        $this->data[$this->mode][] = $sql;
        return $this;
    }

    /**
     * 分组
     * @param string|array $column
     * @return $this
     */
    public function groupBy(string|array $column)
    {
        if (is_array($column)) {
            foreach ($column as $val) {
                $this->groupBy($val);
            }
            return $this;
        }
        $this->data['group'][] = SqlUtil::wrapColumn($column);
        return $this;
    }

    /**
     * 排序
     * @param string $column
     * @param string $direct
     * @return $this
     */
    public function orderBy(string $column, string $direct = 'DESC')
    {
        //排序方向白名单(入口级校验，与Model::orderBy一致)
        $direct = strtoupper(trim($direct));
        if (!in_array($direct, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException('不支持的排序方式，仅允许 asc/desc:' . $direct);
        }
        $this->data['order'][] = [SqlUtil::wrapColumn($column), $direct];
        return $this;
    }

    /**
     * 原始查询字符串  直接传递having字符串
     * @param string $sql
     * @return $this
     */
    public function having(string $sql)
    {
        //原生having片段统一安全校验(严格模式：拦截注释/分号/危险函数/子查询关键字)
        SqlUtil::guardSqlFragment($sql, false);
        $this->data['having'][] = $sql;
        return $this;
    }

    /**
     * 参数化的having条件
     * 值在真正拼接HAVING子句时(build)才生成占位参数并绑定，
     * 避免COUNT等不含HAVING的构建产生多余的绑定参数
     * @param string $column 字段名(已经formatColumn处理)
     * @param string $op 操作符(已经白名单校验)
     * @param mixed $val 值
     * @return $this
     */
    public function havingCond(string $column, string $op, mixed $val)
    {
        $this->data['having'][] = [$column, $op, $val];
        return $this;
    }

    /**
     * 更改为And链接模式
     * @return $this
     */
    public function and(): Wrapper
    {
        $this->mode = 'and';
        return $this;
    }

    /**
     * or之后的连接方式均为or链接
     * @return $this
     */
    public function or(): Wrapper
    {
        $this->mode = 'or';
        return $this;
    }

    /**
     * 获取当前的链接方式
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * 设置拼接类型
     * @param string $type
     * @return $this
     */
    public function setBuildType(string $type): Wrapper
    {
        $this->buildType = $type;
        return $this;
    }

    /**
     * 拼接为sql字符串
     * 此处wrapper拼接的是where之后的字符串。
     * @return string
     */
    public function build(): string
    {
        /**
         * SQL查询与顺序无关 先拼接and 再拼接or
         */
        $where = [
            'and' => [],
            'or' => []
        ];

        /**
         * @var $val Param | string
         */
        foreach ($this->data['and'] as $val) {
            if (is_string($val)) {
                $where['and'][] = $val;
            } else {
                $where['and'][] = $val->toString($this->dataHolder);
            }
        }

        /**
         * @var $val Param | string
         */
        foreach ($this->data['or'] as $val) {
            if (is_string($val)) {
                $where['or'][] = $val;
            } else {
                $where['or'][] = $val->toString($this->dataHolder);
            }
        }

        $orStr = implode(' OR ', $where['or']);

        $whereStr = implode(' AND ', $where['and']);
        /**
         * Or条件拼接好放在最后
         */
        if ($orStr) {
            $whereStr .= ' OR ' . $orStr;
        }

        if ($whereStr) {
            $whereStr = ' WHERE ' . $whereStr;
        }

        /**
         * 分组
         */
        if ($this->data['group']) {
            $groupStr = ' GROUP BY ' . implode(',', $this->data['group']);
            $whereStr .= $groupStr;
        }

        /**
         * having
         * 数组条目为参数化条件，在此处才生成占位参数
         */
        if ($this->data['having'] && $this->buildType != self::BUILD_TYPE_COUNT) {
            $havingSql = [];
            foreach ($this->data['having'] as $having) {
                if (is_array($having)) {
                    //参数化的having条件 [column, op, val]
                    list($havingColumn, $havingOp, $havingVal) = $having;
                    list($havingKey,) = $this->dataHolder->addData('having_' . $havingColumn, $havingVal);
                    $havingSql[] = $havingColumn . ' ' . $havingOp . ' ' . $havingKey;
                } else {
                    $havingSql[] = $having;
                }
            }
            $whereStr .= ' HAVING ' . implode(',', $havingSql);
        }

        /**
         * 排序
         */
        if ($this->data['order'] && $this->buildType != self::BUILD_TYPE_COUNT) {
            $orderArr = [];
            foreach ($this->data['order'] as list($column, $direct)) {
                $orderArr[] = $column . ' ' . $direct;
            }
            $orderStr = ' ORDER BY ' . implode(',', $orderArr);
            $whereStr .= $orderStr;
        }


        if ($this->buildType == self::BUILD_TYPE_COMMON) {
            //拼接limit参数
            $whereStr .= $this->limit;
        }
        return $whereStr;
    }

    /**
     * limit 偏移
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit(int $limit, int $offset = 0)
    {
        if ($limit > PHP_INT_MAX || $limit < 0) {
            throw new \InvalidArgumentException("limit must between 0 and " . PHP_INT_MAX);
        }

        if ($offset > PHP_INT_MAX || $offset < 0) {
            throw new \InvalidArgumentException("offset must between 0 and " . PHP_INT_MAX);
        }

        $this->limit = " Limit " . $offset . ',' . $limit;
        return $this;
    }

    /**
     * and之后操作的条件都为and链接
     * 默认也是 and模式
     * @return string
     */
    public function __toString(): string
    {
        return $this->build();
    }

    /**
     * 嵌套的子查询
     * @return Wrapper
     */
    public function nested(): Wrapper
    {
        /**
         * 生成一个新的构造器并赋予父类的值
         */
        return new self($this);
    }

    /**
     * 嵌套查询的字符串生成后合并到主查询
     * 合并后会返回主查询的类
     */
    public function endNested()
    {
        if (!$this->parent) {
            throw new \InvalidArgumentException("Not Found Parent Wrapper");
        }
        //合并信息
        $this->parent->mergeWrapperData('(' . $this->build() . ')');
        return $this->parent;
    }

    /**
     * 合并其他的sql条件 原始条件
     * 比如子查询的条件
     */
    public function mergeWrapperData(string $data)
    {
        $this->data['and'][] = $data;
    }

}