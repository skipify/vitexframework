<?php


namespace vitex\service\model\sql;

/**
 * 占位数据暂存  一个查询wrapper只能有一个 用来去重
 * @package vitex\service\model\sql
 */
class Holder
{
    /**
     * 生成占位Key的时候的前缀
     * @var string
     */
    private string $keyPrefix = '';

    /**
     * 防止名字重复的自增
     * @var int
     */
    private int $increment = 1;
    /**
     * 名称
     * @var string
     */
    public string $holdName;

    /**
     * 存储数据
     * @var array
     */
    private array $data = [];

    /**
     * @return string
     */
    public function getHoldName(): string
    {
        return $this->holdName;
    }

    /**
     * @param string $holdName
     */
    public function setHoldName(string $holdName): void
    {
        $this->holdName = $holdName;
    }

    /**
     * @param string $keyPrefix
     */
    public function setKeyPrefix(string $keyPrefix): void
    {
        $this->keyPrefix = $keyPrefix;
    }


    /**
     * 获取数据  key=>val
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 保存数据
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * 重置缓存数据
     */
    public function reset(): void
    {
        $this->increment = 1;
        $this->data = [];
    }

    /**
     * 添加Key值到缓存数据中，如果已经存在则会根据Key自动生成一个key
     * 该方法会返回一个新生成的key 用于占位只用
     * 会返回2个key  一个是val的key 一个是 trailKey
     * 返回的Key会自动添加 ： 前缀 作为占位使用
     * @param string $key
     * @param mixed $val 当为数组时  为处理 where in 参数
     * @return array
     */
    public function addData(string $key, mixed $val, mixed $trailVal = ''): array
    {
        $key = SqlUtil::cleanColumn($key);
        /**
         * 添加占位的Key前缀
         */
        $key = $this->keyPrefix . $key;

        /**
         * 生成新的key
         */
        if (isset($this->data[$key])) {
            $key = $key . '_' . $this->increment;
            $this->increment += 1;
        }

        /**
         * 处理占位信息
         */
        if (is_array($val)) {
            /**
             * 多占位符 where in
             * where in (:in_column0,:in_column1)
             */
            $inKeys = [];
            foreach ($val as $_k => $_v) {
                $inKeys[] = ':in_' . $key . $_k;
                $this->data['in_' . $key . $_k] = $_v;
            }
            //返回的也是二维数组
            return [
                implode(',', $inKeys), ''
            ];
        }
        //单占位
        $this->data[$key] = $val;
        if ($trailVal != '' && $trailVal != null) {
            $this->data[$key . '_trail'] = $trailVal;
        }
        return [':' . $key, ':' . $key . '_trail'];
    }

}