<?php declare(strict_types=1);

namespace vitex\service;

use DI\ContainerBuilder;
use vitex\ext\Model;
use vitex\helper\attribute\AttributeTemporaryStore;
use function DI\create;
use function DI\factory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use vitex\core\Env;
use vitex\core\Request;
use vitex\core\Response;
use vitex\service\log\Log;
use vitex\service\session\drivers\FileDriver;
use vitex\service\session\SessionDriverInterface;

/**
 * 容器服务
 */
class Container implements ContainerInterface
{
    /**
     * php-di实例
     * @var
     */
    private $phpDi;

    public function __construct()
    {
        /**
         * 声明容器
         */
        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            /**
             * 系统单例注入
             */
//            Request::class => factory([Request::class, 'getInstance']),
//            Response::class => factory([Response::class, 'getInstance']),
//            Env::class => factory([Env::class, 'getInstance']),
            /**
             * session 内容
             */
            //SessionDriverInterface::class => create(FileDriver::class),
            /**
             * 日志记录  如果需要改为其他引擎则做好映射即可
             */
            LoggerInterface::class => create(Log::class)
        ]);
        $this->phpDi = $builder->build();
    }

    /**
     * 获取一个实例
     * @param string $name
     * @return mixed|string
     */
    public function get($name)
    {

        $instance = $this->phpDi->get($name);

        if ($instance) {
            $attributeStore = AttributeTemporaryStore::instance();
            /**
             * 初始化模型类
             */
            $attributeMode = $attributeStore->get(AttributeTemporaryStore::TABLE);
            if (isset($attributeMode[$name])) {
                /**
                 * @var $instance Model
                 */
                $instance->setPk($attributeMode[$name]['primaryKey']);
                $instance->setTable($attributeMode[$name]['tableName']);
            }
        }

        return $instance;
    }

    /**
     * 根据名字获取实例
     * 每次获取都是一个新的实例
     * 传递参数时可以覆盖自动注入的参数
     * @param $name
     * @param array $parameters
     * @return mixed
     */
    public function make($name, array $parameters = [])
    {
        return $this->phpDi->make($name, $parameters);
    }

    /**
     * 判断实例是否存在
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->phpDi->has($name);
    }

    /**
     * 设置实例
     * 设置的内容是
     * @param $name
     * @param $instance
     */
    public function set($name, $instance)
    {
        $this->phpDi->set($name, $instance);
    }

    /**
     * 使用给定的参数执行指定的函数
     * @param $callable
     * @param array $parameters
     */
    public function call($callable, array $parameters = [])
    {
        $this->phpDi->call($callable, $parameters);
    }
}