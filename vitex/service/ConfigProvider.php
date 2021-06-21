<?php declare(strict_types=1);
/**
 * 初始注册的服务和配置
 */

namespace vitex\service;


use vitex\core\Exception;
use vitex\service\amqp\AmqpConfig;
use vitex\service\config\CookieConfig;
use vitex\service\session\SessionConfig;
use vitex\service\model\DatabaseConfig;

class ConfigProvider
{
    /**
     * 保存配置的容器
     * @var array
     */
    private $settings;

//    /**
//     * 框架扫描的注解目录
//     * @var string[]
//     */
//    private $frameScanDir = [
//        VITEX_BASE_PATH
//    ];


    public function __construct()
    {

        $this->settings = [
            'debug' => false,
            /**
             * annotation
             * 注解扫描目录
             */
            'attribute_scan_dir' =>[],
            /**
             * 运行时缓存目录
             */
            'runtime_dir' => sys_get_temp_dir(),
            // View
            'templates.path' => './templates', //模板的默认路径
            'templates.ext' => '.html',//模板文件的默认扩展名，当省略扩展名时会自动添加
            'view' => '\vitex\View', //view类，可以替换为其他的view层
            'callback' => 'callback', //jsonp时自动获取的值
            'csrf.open' => true,
            'csrf.onmismatch' => null,//一个回调方法 callable，当token不匹配出错的时候回执行
            'csrf.except' => [], //排除的路由规则
            'router.grouppath' => '',
            'router.compatible' => false, //路由兼容模式，不支持pathinfo的路由开启
            'router.case_sensitive' => false, //是否区分大小写
            'methodoverride.key' => '__METHOD', //url request method 重写的key

            /* 请使用新的cookie配置*/
            'cookie' => (CookieConfig::fromArray([]))->toArray(),
            /**
             * 会话管理
             * file  cache memcached redis sqlite native//
             */
            'session' => (SessionConfig::fromArray([]))->toArray(),


            /**
             * 打印到屏幕的日志格式，默认为html格式，可以更改为txt格式
             * html|text 两种类型
             */
            'log.format' => 'html',
            /**
             * 如果指定logger的类型需要传递一个 Psr\Log\LoggerInterface 的实现
             * 如果不传递任何值则会默认使用文件日志 存储到临时目录中
             */
            'log' => null,
            /**
             * cache 的规则
            'cache' => [
                'driver' => CacheStore::REDIS, //可以获得driver
                CacheStore::REDIS =>
                [
                    'instance' => null, //redis实例
                    'host' => '',
                    'port' => 123,
                    'password' => '',
                    'databaseId' => 0,
                    'sentinel' => [
                    'master' => 'T1',
                    'nodes' => [
                        [
                            "host" => '192.168.0.1',
                            'port' => 17001
                        ],
                        [
                            "host" => '192.168.0.2',
                            'port' => 17002
                        ],
                        [
                            "host" => '192.168.0.3',
                            'port' => 17003
                        ],
                        ]
                    ]
                ],
                CacheStore::SQLLITE3 => [
                    'db' => 'xx',//数据库
                    'table' => 'xx'//表名
                ],
                CacheStore::MEMCACHED =>  [
                    'instance' => null, //实例 或者下方的 host/port
                    'host' => '',
                    'port' => 123
                ],
                CacheStore::MONGODB =>       [
                    'instance' => null, //实例或者下方的配置
                    'host' => '',
                    'port' => 123,
                    'database' => '', //数据库
                    'collection' => ''//表/集合
                ],
                CacheStore::PHP_FILE => [
                    'path' => '/path/log'
                ],
                CacheStore::FILE => [
                    'path' => '/path/log'
                ]
            ]
             *
             */
            'cache' => null,
            'charset' => 'utf-8',
            'request.trim' => true,
            /**
             * 数据库配置
             */
            'database' => [
                /**
                 * 默认数据库
                 */
//                'db' => (DatabaseConfig::fromArray(['host'=>'','database'=>'']))->toArray(),
                /**
                 * 主从数据库的主数据库
                 */
                'master' => (new DatabaseConfig())->toArray(),
//                'slaver' => (DatabaseConfig::fromArray(['host'=>'','database'=>'']))->toArray()
            ],
            'amqp' =>(AmqpConfig::fromArray([]))->toArray()
        ];
    }

    /**
     * 获取所有的配置文件
     * @return array
     */
    public function get()
    {
        return $this->settings;
    }

    /**
     * 设置配置文件
     * @param  $name
     * @param null $val
     * @return self
     * @throws Exception
     */
    public function setConfig($name, $val = null)
    {
        if (is_array($name)) {
            foreach ($name as $k=>$v){
                if(is_array($v) && $this->settings[$k]){
                    $this->settings[$k] = array_merge($this->settings[$k],$v);
                } else {
                    $this->settings[$k] = $v;
                }
            }
        } elseif ($val === null) {
            if (file_exists($name)) {
                $configs = include $name;
                $this->settings = array_merge($this->settings, $configs);
            } else {
                throw new Exception("不存在的配置文件:" . $name);
            }
        } else {
            if(is_array($val) && $this->settings[$name]){
                $this->settings[$name] = array_merge($this->settings[$name],$val);
            } else {
                $this->settings[$name] = $val;
            }
        }
        $this->combineScanDir();
        return $this;
    }

    /**
     * 合并框架的扫描目录到系统中
     */
    private function combineScanDir(){
        //暂时不扫描当前框架
//        if(!in_array(VITEX_BASE_PATH,$this->settings['attribute_scan_dir'])){
//            $this->settings['attribute_scan_dir'][] = VITEX_BASE_PATH;
//        }
    }

    /**
     * 获取配置
     * @param string $name 配置名
     * @return mixed
     */
    public function getConfig($name)
    {
        return $this->settings[$name] ?? null;
    }

}