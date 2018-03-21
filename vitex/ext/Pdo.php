<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex\ext;

use vitex\core\Exception;
use vitex\Middleware;

class Pdo extends Middleware
{
    public $engine = 'mysql';
    public $pdo    = null;
    public $sql    = '';

    /**
     * @var \PDOStatement
     */
    public $sth = null;
    public $error; //错误信息

    public function __construct($setting, $username = '', $password = '')
    {
        if (!$setting) {
            throw new Exception('数据库链接信息不能为空',Exception::CODE_PARAM_NUM_ERROR);
        }
        if (is_resource($setting)) {
            $this->pdo = $setting;
        } else if (is_array($setting)) {
            $username = $username ?: ($setting['username'] ?? '');
            $password = $password ?: ($setting['password'] ?? '');
            try {
                $this->pdo = new \Pdo($this->getDsn($setting), $username, $password);
            } catch (\PDOException $e) {
                echo '无法连接到数据库';
                exit;
            }
        } else {
            try {
                $this->pdo = new \Pdo($setting, $username, $password);
            } catch (\PDOException $e) {
                echo '无法连接到数据库';
                exit;
            }
        }
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * 获取DSN连接字符
     * @param  array  $p    包含database、host、charset的链接字符
     * @return string dsn
     */
    public function getDsn($p)
    {
        $this->engine = $p['engine'] ?? $this->engine;
        return $this->engine . ':dbname=' . $p['database'] . ';host=' . $p['host'] . ';charset=' . ($p['charset'] ?? 'utf8');
    }

    /**
     * 执行sql语句，支持预处理语句
     * @param  string $sql              sql语句
     * @param  array  $arr              预处理时传递的参数
     * @return int    影响的行数
     */
    public function execute($sql, $arr = [])
    {
        $this->sql = $sql;
        $sth       = $this->pdo->prepare($sql);
        try {
            $sth->execute($arr);
        } catch (\PDOException $e) {
            $this->errorInfo($sql, $e->getMessage());
            throw $e;
        }
        $count = $sth->rowCount();

        return $count;
    }

    /**
     * 执行sql语句返回statement 支持预处理
     * @param  string $sql                    sql语句
     * @param  array  $arr                    预处理参数
     * @return object PDOStatement的实例
     */
    public function query($sql, $arr = [])
    {
        $this->sql = $sql;
        $this->sth = $this->pdo->prepare($sql);
        try {
            $this->sth->execute($arr);
        } catch (\PDOException $e) {
            $this->errorInfo($sql, $e->getMessage());
            throw $e;
        }
        return $this->sth;
    }

    /**
     * 执行返数据，生成器数据
     * @param int|string $mode 返回的数据模式，默认为class模式
     * @return \Generator 一个信息对象
     */
    public function fetch($mode = \PDO::FETCH_CLASS)
    {
        $run = null;
        try {
            $run = $this->sth->fetch($mode);
        } catch (\PDOException $e) {
            $this->errorInfo('', $e->getMessage());
            throw $e;
        }
        yield $run;
    }

    /**
     * 返回全部的复合查询的数据
     * @param  int|string $mode                         返回的数据模式，默认为class模式
     * @return array      一个包含对象的数组
     */
    public function fetchAll($mode = \PDO::FETCH_CLASS)
    {
        $rows = [];
        try {
            $rows = $this->sth->fetchAll($mode);
        } catch (\PDOException $e) {
            $this->errorInfo('', $e->getMessage());
            throw $e;
        }
        return $rows;
    }

    /**
     * 返回刚才操作的行的id 对auto_increment的值有效
     *
     * @author skipify
     *
     * @return int
     */
    public function lastId()
    {
        return $this->pdo->lastInsertId();
    }

    public function close()
    {
        $this->pdo = null;
        $this->sth = null;
    }

    /**
     * 错误信息
     *
     * @author skipify
     *
     * @param $sql
     * @param $error
     */
    public function errorInfo($sql, $error)
    {
        if ($this->vitex->getConfig('debug')) {

            $msg = "<p style='color:red;font-weight:bold'>" . $sql . "<p>";
            $msg .= "<p>" . $error . "</p>";
        } else {
            $msg = 'SQL:' . $sql . '  Error: ' . $error;
        }
        $this->error = $msg;
        $this->vitex->log->error($msg);
    }

    public function call()
    {
        $this->vitex->pdo = $this;
        $this->runNext();
    }

    public function __destruct()
    {
        $this->close();
    }
}
