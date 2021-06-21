<?php


namespace vitex\service\model\sql;


use vitex\core\Exception;
use vitex\service\model\PdoUtil;

/**
 * 数据库表的常用操作
 * @package vitex\service\model\sql
 */
class Table
{

    //todo index  alter 等命令
    /**
     * 从数据库读取表结构元数据
     * @return array/string
     * @throws Exception
     */
    final public function getMeta(string $tableName)
    {
        $sql = 'describe ' . $tableName;
        $this->sql = $sql;

        $sth = PdoUtil::instance()->getMaster()->query($sql);
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $metaData = [];
        foreach ($rows as $row) {
            $metaData[] = [
                'field' => $row['Field'],
                'typeArr' => $this->parseType($row['Type']),
                'type' => $this->parseType($row['Type'])[0],
                'allowNull' => $row['Null'] == 'NO' ? false : true,//是否可以是NULL
                'key' => $row['Key'],
                'default' => $row['Default'],
                'extra' => $row['Extra']
            ];
        }
        return $metaData;
    }


    /**
     * 解析数据库的类型和数据
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

}