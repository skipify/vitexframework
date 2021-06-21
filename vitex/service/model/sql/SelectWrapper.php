<?php


namespace vitex\service\model\sql;


class SelectWrapper extends Wrapper
{
    /**
     * 查询字符串
     * @var array
     */
    private array $column = [];

    /**
     * 表名
     * @var string
     */
    private string $table = '';

    private bool $isDistinct = false;

    /**
     * 设置查询的字段
     * 参数格式比较复杂 可以为   'field1,field2','field3'  也可以为   ['field1,field2'],'field3'
     * @param mixed ...$args
     * @return $this
     */
    public function select(...$args)
    {
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $this->select(...$arg);
            } elseif (str_contains($arg, ',') && !str_contains($arg,'(') && !str_contains($arg,')')) {
                $_args = explode(',', $arg);
                $this->select(...$_args);
            } else {
                if ($arg != '*') {
                    $this->column[] = SqlUtil::wrapSelectColumn($arg);
                } else {
                    $this->column[] = '*';
                }
            }
        }
        return $this;
    }

    public function from(string $table): SelectWrapper
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 获取查询的表名
     * @return string
     */
    public function getTable(): string
    {
        return SqlUtil::wrapSelectColumn($this->table);
    }

    /**
     * 去重查询
     * @param mixed ...$args
     * @return $this
     */
    public function distinct(...$args)
    {
        $this->isDistinct = true;
        if (count($args)) {
            $this->select(...$args);
        }
        return $this;
    }

    /**
     * 查询总条数
     * @param $field
     * @return string
     */
    public function count($field)
    {
        if ($field == '*') {
            return 'count(*) as num';
        }
        //一般不会用*去重吧
        if ($this->isDistinct) {
            return sprintf('count(DISTINCT %s) as num', SqlUtil::wrapSelectColumn($field));
        } else {
            return sprintf('count(%s) as num', SqlUtil::wrapSelectColumn($field));
        }
    }

    /**
     * 生成查询字段列表
     */
    public function toSql()
    {
        if (!$this->column) {
            $this->column[] = '*';
        }
        return ($this->isDistinct ? ' DISTINCT ' : '') . implode(',', $this->column);
    }
}