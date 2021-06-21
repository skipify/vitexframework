<?php

/**
 * Vitex 一个基于php8.0开发的 快速开发restful API的微型框架
 * @version  2.0.0
 *
 * @package vitex\service\model
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex\service\model;

use vitex\service\model\exception\NotFoundConfigException;
use vitex\Vitex;

/**
 * pdo 助手类 为一个单例，保证同一个数据库链接可以复用
 * @package vitex\service\model
 */
class PdoUtil
{
    private const SLAVER = 'slaver';

    private const MASTER = 'master';

    private const DEFAULT = "db";
    /**
     * @var $_instance PdoUtil
     */
    private static $_instance;

    /**
     * 连接池
     * @var array
     */
    private array $pool = [];

    private function __construct()
    {

    }

    /**
     * 获得Pdo链接的单例
     * @return PdoUtil
     */
    public static function instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 获取主数据库配置
     * @return Pdo
     */
    public function getMaster()
    {
        return $this->getByConfigKey(self::MASTER);
    }

    /**
     * 获取从数据库配置
     * @return Pdo
     */
    public function getSlaver()
    {
        $pdo = $this->getByConfigKey(self::SLAVER);
        if (!$pdo) {
            $pdo = $this->getByConfigKey(self::MASTER);
            $this->pool[self::SLAVER] = $pdo;
        }
        return $pdo;
    }

    /**
     * 根据database配置中的key值获得信息
     * @param $key
     * @return mixed|Pdo
     * @throws NotFoundConfigException
     */
    public function getByConfigKey($key)
    {
        if (isset($this->pool[$key])) {
            return $this->pool[$key];
        }
        $vitex = Vitex::getInstance();
        $databaseConfig = $vitex->getConfig('database');
        $isCompat = false;

        /**
         * 没有从的时候直接读主
         */
        if($key == self::SLAVER && !isset($databaseConfig[self::SLAVER])){
            $config = $databaseConfig[self::MASTER];
            $isCompat = true;
        } else {
            $config = $databaseConfig[$key];
        }

        $pdo = new Pdo($config);
        $this->pool[$key] = $pdo;
        if($isCompat){
            $this->pool[self::SLAVER] = $pdo;
        }
        return $pdo;
    }

    /**
     * 根据配置获取Pdo链接
     * @param array $config
     * @return mixed|Pdo
     */
    public function getByConfig(array $config)
    {
        $key = md5(serialize($config));
        if (isset($this->pool[$key])) {
            return $this->pool[$key];
        }
        $pdo = new Pdo($config);
        $this->pool[$key] = $pdo;
        return $pdo;
    }
}