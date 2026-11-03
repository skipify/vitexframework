<?php declare(strict_types=1);
/**
 * 初始注册的服务和配置
 */

namespace vitex\service;


use vitex\core\Exception;
use vitex\service\crypto\ConfigCipher;

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
            /**
             * .env 文件路径（字符串或路径数组），每次setConfig时自动加载到环境变量，
             * 已存在的真实环境变量优先，文件值不覆盖；留空不加载
             */
            'env_file' => '',
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
            //禁止默认密钥：留空时启用加密的组件会直接抛出异常，强制配置私有随机密钥
            'cookies.secret_key' => '',
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

            /**
             * 配置值加密主密钥（ENC1: 密文用，16 字节 SM4 密钥的 Base64）。
             * 留空时回退环境变量 SIGNHUB_CONFIG_KEY；两者都缺省且有 ENC1: 配置值时拒绝启动
             */
            'config.enc1_key' => '',
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
        $this->loadEnvFiles();
        $this->resolveEnc1Values();
        return $this;
    }

    /**
     * 加载配置项 env_file 指定的 .env 文件到环境变量，
     * 先于 ENC1: 解密执行，因此主密钥环境变量也可以放在 env 文件中
     */
    private function loadEnvFiles(): void
    {
        $files = $this->settings['env_file'] ?? '';
        if ($files === '' || $files === null) {
            return;
        }
        EnvLoader::load($files);
    }

    /**
     * 解析配置中的 ENC1: 加密值（SCF1 格式，SM4-GCM 国密，见 vitex\service\crypto\ConfigCipher）。
     *
     * 主密钥来源：配置项 config.enc1_key 优先，其次环境变量 SIGNHUB_CONFIG_KEY。
     * fail-closed：存在 ENC1: 值而主密钥缺失，或任一值解密失败，抛出异常拒绝启动。
     * 解密后的明文值不再带 ENC1: 前缀，重复调用天然幂等。
     */
    private function resolveEnc1Values()
    {
        $key = ConfigCipher::findMasterKey($this->settings['config.enc1_key'] ?? null);
        array_walk_recursive($this->settings, function (&$v) use ($key): void {
            if (is_string($v) && ConfigCipher::isEncrypted($v)) {
                if ($key === null) {
                    throw new Exception(
                        '检测到 ENC1: 加密配置值，但缺少主密钥：'
                        . '请设置配置项 config.enc1_key 或环境变量 SIGNHUB_CONFIG_KEY'
                        . '（16 字节 SM4 密钥的 Base64，openssl rand -base64 16 生成）。'
                        . 'fail-closed：拒绝以密文配置启动'
                    );
                }
                $v = ConfigCipher::decrypt($key, $v);
            }
        });
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