<?php

include(__DIR__ . '/CommandInterface.php');

/**
 * model命令解析
 * Class ModelCommand
 */
class ModelCommand implements CommandInterface
{
    private array $args = [];

    private PDO $pdo;

    public function __construct($args)
    {
        if ($args) {
            $this->args = $args;
        }
        $dsn = 'mysql:host=' . $args['host'] . ';dbname=' . $args['database'] . ';';
        $this->pdo = new PDO($dsn, $args['username'], $args['password']);
    }

    public function run()
    {
        $sql = 'describe `' . $this->args['table'] . '`;';
        $sth = $this->pdo->query($sql);
        $ret = $sth->fetchAll(PDO::FETCH_ASSOC);

        $this->generateFile($ret);
    }

    private function generateFile(array $fields)
    {
        $_fileds = [];
        foreach ($fields as $field) {
            $_fileds[$field['Field']] = [
                'fieldName' => $field['Field'],
                'allowNull' => $field['Null'] == 'NO' ? false : true,
                'dataType' => strtoupper(preg_replace('/[\(\)0-9]/', '', $field['Type'])),
                'default' => $field['Default']
            ];
        }

        $str = '<' . '?php';
        $str .= "\n";
        $str .= "namespace app\\entity;\n";
        $str .= "use vitex\core\attribute\model\Table;\n";
        $str .= "use vitex\core\attribute\model\TableField;\n";
        $str .= "use vitex\service\model\BaseEntity;\n";
        $str .= "/*Table {$this->args['table']} Entity*/\n";
        $str .= '#[Table("'.$this->args['table'].'")]' . "\n";
        $str .= "class " . ucfirst($this->args['table']) . ' extends BaseEntity';
        $str .= "{\n";

        $str .= $this->generateField($_fileds);
        $str .= "\n\n";
        $str .= "    protected const _fieldDescribe = " . var_export($_fileds,true) .";\n";

        $str .= '    public function __toString(): string
    {
        return json_encode($this);
    }';
        $str .= "\n";

        $str .= '}';
        $fileName = ucfirst($this->args['table']) . '.php';
        if($this->args['path']){
            $fileName = $this->args['path'] .'/' . $fileName;
        }
        file_put_contents($fileName,$str);
    }

    /**
     * 生成各个字段名字
     * @param $fields
     * @return string
     */
    private function generateField($fields)
    {
        $str = "";
        foreach ($fields as $field) {
            $str .= '    public ' . $this->fieldMap($field['dataType']) . ' $' . lcfirst(Utils::camelCase($field['fieldName'])) . ";\n";
        }
        return $str;
    }

    /**
     * 转换类型
     * @param $type
     * @return string
     */
    private function fieldMap($type)
    {
        $fields = [
            'INT' => 'int',
            'TINYINT' => 'int',
            'SMALLINT' => 'int',
            'MEDIUMINT' => 'int',
            'BIGINT' => 'int',
            'FLOAT' => 'float',
            'DOUBLE' => 'float',
            'DECIMAL' => 'float',
            'DATE' => 'string',
            'TIME' => 'string',
            'YEAR' => 'string',
            'DATETIME' => 'string',
            'TIMESTAMP' => 'string',
            'CHAR' => 'string',
            'VARCHAR' => 'string',
            'TINYTEXT' => 'string',
            'TEXT' => 'string',
            'MEDIUMTEXT' => 'string',
            'LONGTEXT' => 'string',
            'GEOMETRY' => 'string',
            'POINT' => 'string',
            'LINESTRING' => 'string',
            'POLYGON' => 'string',
            'MULTIPOINT' => 'string',
            'MULTILINESTRING' => 'string',
            'MULTIPOLYGON' => 'string',
            'GEOMETRYCOLLECTION' => 'string'
        ];
        return isset($fields[$type]) ? $fields[$type] : 'string';
    }
}