# 配置文件

新版配置文件都提供了 配置生成器

并且有些配置页对应做了分组和调整 



## cookie配置
旧版
```

    'cookies.encrypt' => true, //是否启用cookie加密
    'cookies.lifetime' => '20 minutes',
    'cookies.path' => '/',
    'cookies.domain' => '',
    'cookies.secure' => false,
    'cookies.httponly' => false,
    'cookies.secret_key' => 'Vitex is a micro restfull framework',
```

改为

```
'cookie' => [
    'encrypt' => true, //是否启用cookie加密
    'lifetime' => '20 minutes',
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => false,
    'secret_key' => 'Vitex is a micro restfull framework',
]

//配置可以使用配置生成器来生成

$cookieConfig = new CookieConfig();
$cookieConfig->setPath("/");
....

$cookieConfig->toArray();
```

Session配置修改
旧版

```
    /**
     * 会话管理
     * file  cache memcached redis sqlite native//
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
```

新版 
```
'session'=>[
    'driver' => 'native',
    'lifetime' => 15,
    'path' => '',
    'instance' => null
]
```



## 一些类的移动

1. \vitex\ext\Model => \vitex\service\model\Model



## 不支持的特性

Request  Response Env 已经不是单例，如果需要获取单例 请从  Vitex::req  Vitex::response Vitex::Env获取