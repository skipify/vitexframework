# 日志

框架集成了 `monolog`库

##符合PSR规范的日志类

```
$log = $this->container->get(Log::class);//单例

$log = $this->log;//和容器中是一个

//此种方式获取一个新对象
$log = new Log($logger); // $logger=null 则为配置文件中的日志配置  
```

emergency alert info debug 等方法均支持

## 静态调用方法

```LogUtil```

此类为一个单例

```
LogUtil::instance($logger);


LogUtil.info("日志",array $context=[])
```