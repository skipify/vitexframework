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
// 入口文件 index.php
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
- 值注入真实环境变量（`putenv` + `$_ENV`/`$_SERVER`），`getenv()`、`$vitex->env` 均可读取
- 在每次 `setConfig()` 时自动执行，且**先于 ENC1: 配置解密**，因此主密钥 `VITEX_CONFIG_KEY` 也可以放在 env 文件里

配合配置值加密的典型用法：

```bash
# /data/deploy/shared/.env （仓库外，权限 600）
VITEX_CONFIG_KEY=MDEyMzQ1Njc4OWFiY2RlZg==
```

```php
// 应用配置文件
return [
    'env_file' => '/data/deploy/shared/.env',
    'database' => [
        'master' => [
            'password' => 'ENC1:U0NGMRf1……',
        ],
    ],
];
```

> 注意：不要把 `VITEX_CONFIG_KEY` 与 `ENC1:` 密文放在同一个 env 文件里（密钥与密文同库会削弱加密意义）。

## 敏感配置加密（ENC1:）

配置文件里经常需要存放数据库密码、第三方密钥等敏感信息，直接写明文会随配置文件进入代码仓库。
Vitex 支持把这些值写成加密形式（`ENC1:` 前缀，类似 Jasypt 的 `ENC(...)`），框架启动时自动解密，
代码中 `getConfig()` 拿到的仍是明文。算法为 SM4-GCM 国密 AEAD 认证加密（`vitex\service\crypto\ConfigCipher`，密文格式 SCF1），可防篡改。

### 1. 生成主密钥

主密钥为 16 字节 SM4 密钥的 Base64 文本，机器生成、无需口令派生：

```bash
openssl rand -base64 16
# 例如：MDEyMzQ1Njc4OWFiY2RlZg==
```

通过环境变量 `VITEX_CONFIG_KEY` 注入（推荐，密钥不落盘）：

```bash
export VITEX_CONFIG_KEY="MDEyMzQ1Njc4OWFiY2RlZg=="
```

也可以写在配置项 `config.enc1_key` 里（优先级高于环境变量，注意不要与密文存放在同一处）：

```php
'config.enc1_key' => 'MDEyMzQ1Njc4OWFiY2RlZg==',
```

> 注意：主密钥丢失后密文将永久无法解密，务必在密钥管理系统等安全的地方托管备份；
> 不要把主密钥提交到代码仓库。

### 2. 生成加密值

用 `ConfigCipher::encrypt()` 把明文加密成 `ENC1:` 值：

```php
use vitex\service\crypto\ConfigCipher;

echo ConfigCipher::encrypt('MDEyMzQ1Njc4OWFiY2RlZg==', 'my-db-password');
// ENC1:U0NGMRf1cJMg……
```

同一明文每次加密结果都不同（每次使用随机 nonce），属正常现象。

### 3. 在配置中使用

把生成的 `ENC1:` 值直接写在配置里，明文与密文可以混用（只有带 `ENC1:` 前缀的字符串会被解密）：

```php
return [
    'database' => [
        'master' => [
            'host' => '127.0.0.1',
            'user' => 'app',
            'password' => 'ENC1:U0NGMRf1cJMg……',
        ],
    ],
];
```

框架单例初始化（`\vitex\Vitex::getInstance(...)`）以及每次调用 `$vitex->setConfig(...)` 时都会自动解密配置中的 `ENC1:` 值，
之后 `$vitex->getConfig()` 获取到的就是明文。

### 4. 解密失败即拒绝启动（fail-closed）

- 存在 `ENC1:` 值但没有主密钥（`config.enc1_key` 与 `VITEX_CONFIG_KEY` 都缺失）时，抛出异常拒绝启动
- 主密钥不匹配或密文被篡改时，抛出异常拒绝启动

不会带着密文或错误配置继续运行。

### 5. 手动解密（非框架环境）

```php
use vitex\service\crypto\ConfigCipher;

$plain = ConfigCipher::decrypt($masterKey, $encValue);      // 解密单个值
$isEnc = ConfigCipher::isEncrypted($value);                 // 是否为 ENC1: 加密值
$array = ConfigCipher::resolve($configArray, $masterKey);   // 递归解密整个配置数组
```

### 6. 密钥轮换

用旧主密钥解出明文，再用新主密钥加密替换所有 `ENC1:` 值即可；替换完成前两种密文的密钥都需保留。