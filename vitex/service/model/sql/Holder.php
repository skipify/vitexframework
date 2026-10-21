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
    /**
     * 存储数据
     * @var array
     */
    private array $data = [];

    /**
     * 通过mergeData合并进来的外部SQL片段的占位数据
     * 这些占位符存在于原始SQL片段中(如whereTuple/whereIn子查询)，
     * 重新构建SQL时不会重新生成，因此reset()时不会被清除
     * @var array
     */
    private array $mergeData = [];

    /**
     * @return string
     */
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
        return $this->data + $this->mergeData;
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
     * 注意：合并的外部SQL片段数据不会被重置，因为这些占位符不会在重新构建时再次生成
     * @return void
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
        if (isset($this->data[$key]) || isset($this->mergeData[$key])) {
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

    /**
     * 合并一段外部SQL片段(如子查询)中的占位数据
     * 片段中的占位符会被重写为不冲突的新key，保证与当前已有的占位数据不冲突，
     * 重写后的SQL片段返回给调用方，由调用方拼接到查询中，执行时统一使用prepare绑定
     * @param string $sql 包含 :key 形式占位符的SQL片段
     * @param array $data 占位数据 key=>val
     * @return string 重写占位符之后的SQL片段
     */
    public function mergeData(string $sql, array $data): string
    {
        if (!$data) {
            return $sql;
        }
        foreach ($data as $key => $val) {
            $newKey = $this->createUniqueKey('sub_' . $key);
            //整词匹配占位符，避免 :id 误替换 :id_1 / :idx
            $pattern = '/:' . preg_quote((string)$key, '/') . '(?![A-Za-z0-9_])/';
            $sql = preg_replace($pattern, ':' . $newKey, $sql);
            $this->mergeData[$newKey] = $val;
        }
        return $sql;
    }

    /**
     * 生成一个不与当前已有占位数据冲突的key
     * @param string $key 期望的key
     * @return string 不冲突的key
     */
    private function createUniqueKey(string $key): string
    {
        if (!isset($this->data[$key]) && !isset($this->mergeData[$key])) {
            return $key;
        }
        for ($i = $this->increment;; $i++) {
            $newKey = $key . '_' . $i;
            if (!isset($this->data[$newKey]) && !isset($this->mergeData[$newKey])) {
                $this->increment = $i + 1;
                return $newKey;
            }
        }
    }
}
