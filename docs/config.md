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
## .env 文件加载

框架没有强依赖 `.env` 文件。当配置需要从其他目录的 `.env` 加载时（例如部署共享目录），在配置项 `env_file` 里指定路径即可（支持字符串或路径数组）：

```php
// Vitex 是单例，配置数组仅在首次 getInstance() 时生效，之后请用 setConfig() 修改
$vitex = \vitex\Vitex::getInstance([
    'env_file' => '/data/deploy/shared/.env',
]);

// 或者写在配置文件里（由 setConfig 加载）
return [
    'env_file' => ['/data/deploy/shared/.env', __DIR__ . '/.env'],
];
```

也可以不经过配置项，直接手动加载：

```php
use vitex\service\EnvLoader;

EnvLoader::load('/data/deploy/shared/.env'); // 返回实际注入的 key=>value
```

加载规则：

- 文件不存在或不可读时静默跳过，不报错
- **已存在的真实环境变量优先**，文件值不会覆盖它们（部署系统注入的变量不受影响）
- 忽略空行与 `#` / `;` 注释行，支持可选的 `export ` 前缀与成对的单双引号
- 行内 `#` 视为值的一部分，密码等含 `#` 的值建议用引号包裹
- 值注入真实环境变量（`putenv` + `$_ENV`/`$_SERVER`），`getenv()` 均可读取
- 在每次 `setConfig()` 时自动执行，且先于 ENC1: 配置解密，因此配置加密的主密钥环境变量（`ConfigCipher::ENV_KEY`）也可以放在 env 文件里

> 注意：不要把配置加密主密钥与 `ENC1:` 密文放在同一个 env 文件里（密钥与密文同库会削弱加密意义）。
