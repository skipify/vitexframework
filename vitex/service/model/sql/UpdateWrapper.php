<?php


namespace vitex\service\model\sql;

/**
 * 修改wrapper
 * @package vitex\service\model\sql
 */
class UpdateWrapper extends Wrapper
{
    /**
     * 修改字段的值
     */
    private const TPL = "%s = %s";
    /**
     * 设置
     * @var array
     */
    private array $set = [];

    /**
     * 原始的设置类型
     * @var array
     */
    private array $setRaw = [];

    public function __construct($parent = null)
    {
        parent::__construct($parent);
        $this->dataHolder->setKeyPrefix('u_');
    }

    /**
     * 修改的设置字段
     * @param string $column
     * @param mixed $val
     * @param $this
     */
    public function set(string $column, mixed $val)
    {
        $this->set[] = [$column, $val];
        return $this;
    }

    /**
     * 设置带有字段配置的 内容
     * 此处设置的内容不会使用占位符预处理  可能存在注入的风险
     * 建议根据情况自行过滤
     * @param string $column
     * @param string $val
     * @return $this
     */
    public function setRaw(string $column, string $val)
    {
        $this->setRaw[] = [
            $column, $val
        ];
        return $this;
    }

    /**
     * 自增
     * @param string|array $column
     * @param int|array $num
     * @return $this
     */
    public function increment(string|array $column, int|array $num)
    {
        return $this->step($column, $num);
    }

    /**
     * 自减
     * @param string|array $column
     * @param int|array $num
     * @return $this
     */
    public function decrement(string|array $column, int|array $num)
    {
        return $this->step($column, $num, false);
    }

    /**
     * 步进操作
     * @param string|array $column
     * @param string|array $num
     */
    private function step(string|array $column, int|array $num, $positive = true)
    {
        if (is_array($column) && count($column) != count($num)) {
            throw new \InvalidArgumentException('传递的字段与自增值无法对应，请查看数量');
        }
        $op = $positive ? ' + ' : ' - ';
        if (is_array($column)) {
            // 字段索引  字段名称
            foreach ($column as $key => $val) {
                $this->step($val, $num[$key], $positive);
            }
            return $this;
        }
        $this->setRaw($column, SqlUtil::wrapColumn($column) . $op . $num);
    }

    /**
     * 生成update的sql  set
     * @return string
     */
    public function toSql()
    {
        $sets = [];
        foreach ($this->set as list($column, $value)) {
            /**
             * @var $columnKey string 占位字符串
             */
            list($columnKey,) = $this->dataHolder->addData($column, $value);
            $column = SqlUtil::wrapColumn($column);
            $sets[] = sprintf(self::TPL, $column, $columnKey);
        }

        foreach ($this->setRaw as list($column, $value)) {
            $sets[] = sprintf(self::TPL,SqlUtil::wrapColumn($column),$value);
        }

        return implode(',', $sets);
    }
}