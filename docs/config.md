# 配置文件

- `debug`  配置项是否为调试模式， true为调试模式
- `attribute_scan_dir` 注解的扫描目录
- `runtime_dir` 运行时目录，默认是临时目录，一些缓存以及运行时需要生成的内容会存储在这里
- `templates.path` 模板的默认路径
- `templates.ext` 模板文件的默认扩展名（html），当省略扩展名时会自动添加
- `view` view类(\vitex\View)，可以替换为其他的view层
- `callback` jsonp时自动获取的值(callback)
- `csrf.open` 是否开启 csrf 默认为true
- `csrf.onmismatch`一个回调方法 callable，当token不匹配出错的时候回执行 默认为null
- `csrf.except` 排除的csrf路由规则
- `router.grouppath` 路由文件存储目录 默认为 app/route 目录
- `router.compatible` 路由兼容模式默认false，对于不支持pathinfo的服务器开启此项
- `router.case_sensitive`  路由是否区分大小写
- `methodoverride.key` url request method 重写的key 使用 __METHOD隐藏域


```php
'cookie' => [
    'encrypt' => true, //是否启用cookie加密
    'lifetime' => '20 minutes',
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => false,
    'secret_key' => 'Vitex is a micro restfull framework',
]
```

```php
'session'=>[
    'driver' => 'native', session存储引擎  file  cache memcached redis sqlite native
    'lifetime' => 15, session有效期15分钟
    'path' => '',  文件session存储路径deprecate
    'instance' => null  membercached缓存实例 deprecate
]

```

- `log.format` 打印到屏幕的日志格式，默认为html格式，可以更改为txt格式
- `cache`  查看缓存使用
- `database` 查看Model配置

## 配置文件的加载

```php
$vitex->setConfig([
    "key" => "val"
])

$vitex->setConfig("xxx.php")
```

## 获取配置

```
$vitex->getConfig("key")
```