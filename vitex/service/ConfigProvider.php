<?php declare(strict_types=1);
/**
 * 初始注册的服务和配置
 */

namespace vitex\service;


use vitex\core\Exception;

class ConfigProvider
{
    /**
     * 保存配置的容器
     * @var array
     */
    private $settings;


    public function __construct()
    {

        $this->settings = [
            'debug' => false,
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
            'cookies.encrypt' => true, //是否启用cookie加密
            'cookies.lifetime' => '20 minutes',
            'cookies.path' => '/',
            'cookies.domain' => '',
            'cookies.secure' => false,
            'cookies.httponly' => false,
            'cookies.secret_key' => 'Vitex is a micro restfull framework',
            /**
             * 会话管理
             * file  cache native//
             */
            'session.driver' => 'native',
            /**
             * 会话存活期  分钟
             */
            'session.lifetime' => 15,
            /**
             * 文件保存配置的时候的路径
             */
            'session.file.path' => '',

            /**
             * redis memcache数据缓存时候的实例
             */
            'session.cache.instance' => null,

            /**
             * 打印到屏幕的日志格式，默认为html格式，可以更改为txt格式
             * html|text 两种类型
             */
            'log.format' => 'html',

            'charset' => 'utf-8',
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
     * @param  null $val
     * @throws Exception
     * @return self
     */
    public function setConfig($name, $val = null)
    {
        if (is_array($name)) {
            $this->settings = array_merge($this->settings, $name);
        } elseif ($val === null) {
            if (file_exists($name)) {
                $configs = include $name;
                $this->settings = array_merge($this->settings, $configs);
            } else {
                throw new Exception("不存在的配置文件:" . $name);
            }
        } else {
            $this->settings[$name] = $val;
        }
        return $this;
    }

    /**
     * 获取配置
     * @param  string $name 配置名
     * @return mixed
     */
    public function getConfig($name)
    {
        return $this->settings[$name] ?? null;
    }

}