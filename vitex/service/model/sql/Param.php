<?php


namespace vitex\service\model\sql;

/**
 * 用于记录SQL查询条件等的数据
 * @package vitex\service\model\sql
 */
class Param
{
    /**
     * 数据库字段列
     * @var string
     */
    private string $column;

    /**
     * 数据库操作
     * @var string
     */
    private string $opTpl;

    /**
     * 操作值
     * 如果值为 php 的 null类型则会忽略此字段
     * @var mixed
     */
    private mixed $val;

    /**
     * 需要多个操作值的尾部操作值
     * @var mixed
     */
    private mixed $trailVal;

    /**
     * 列参数在前 value参数在后的形式
     * @var bool
     */
    private bool $columnFirst = true;

    /**
     * 是否只有一个参数
     * @var bool
     */
    private bool $isSigle = false;

    /**
     * Param constructor.
     * @param string $opTpl
     * @param string $column
     * @param mixed|null $val 数组 或者是 字符串，目前数组时是 where in 使用
     * @param mixed|null $trailVal
     */
    public function __construct(string $opTpl, string $column, mixed $val = null, mixed $trailVal = null)
    {
        $this->opTpl = $opTpl;
        $this->column = $column;
        $this->val = $val;
        $this->trailVal = $trailVal;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return SqlUtil::wrapColumn($this->column);
    }

    /**
     * @param string $column
     */
    public function setColumn(string $column): void
    {
        $this->column = $column;
    }

    /**
     * @return string
     */
    public function getOpTpl(): string
    {
        return $this->opTpl;
    }

    /**
     * @param string $opTpl
     */
    public function setOpTpl(string $opTpl): void
    {
        $this->opTpl = $opTpl;
    }

    /**
     * @return string|array|null
     */
    public function getVal(): string|array|null
    {
        return $this->val;
    }

    /**
     * @param string $val
     */
    public function setVal(string $val): void
    {
        $this->val = $val;
    }

    /**
     * @return string
     */
    public function getTrailVal(): string|null
    {
        return $this->trailVal;
    }

    /**
     * @param string $trailVal
     */
    public function setTrailVal(string $trailVal): void
    {
        $this->trailVal = $trailVal;
    }

    /**
     * @return bool
     */
    public function isColumnFirst(): bool
    {
        return $this->columnFirst;
    }

    /**
     * @param bool $columnFirst
     */
    public function setColumnFirst(bool $columnFirst): void
    {
        $this->columnFirst = $columnFirst;
    }

    /**
     * @return bool
     */
    public function isSigle(): bool
    {
        return $this->isSigle;
    }

    /**
     * @param bool $isSigle
     */
    public function setSigle(bool $isSigle = true): void
    {
        $this->isSigle = $isSigle;
    }

    /**
     * 用于处理 拼接字符串的替换工作
     * 替换的时候会自动处理所有的Key 以及重复Key的问题getTrailVal
     * @param Holder $holder
     * @return string
     */
    public function toString(Holder &$holder)
    {
        /**
         * 使用PDO的 prepare语句  这里需要使用占位符先占用，然后使用execute执行
         * 这里是   holder=>value
         *
         * 占位的 key值 再一次查询中不能重复，因此 如果是有子查询的话 需要统一判断不重复
         * 这里统一在Holder中处理
         */
        if ($this->isSigle() && $this->getVal() === null) {
            // is null 形式  只需要拼接字段
            $str = sprintf($this->getOpTpl(), $this->getColumn());
        } else {
            list($columnKey, $trailColumnKey) = $holder->addData($this->column, $this->getVal(), $this->getTrailVal());
            if ($this->isSigle() && $this->getColumn() === null) {
                //exists 形式，只需要拼接内容
                $str = sprintf($this->getOpTpl(), $columnKey);
            } else {
                if ($this->isColumnFirst()) {
                    //列参数在前 value参数在后的形式
                    $str = sprintf($this->getOpTpl(), $this->getColumn(), $columnKey, $trailColumnKey);
                } else {
                    //例如 find_in_set
                    $str = sprintf($this->getOpTpl(), $columnKey, $this->getColumn(), $trailColumnKey);
                }
            }
        }
        return $str;
    }


}