# 缓存使用

目前系统集成了 `doctrine/cache` 库，可以实现系统的缓存使用。

## 启用缓存配置

系统启用缓存比较简单，只需要简单的配置即可开启。
目前系统支持多种缓存方式，配置各不相同。

支持的格式可以从 `CacheStore`类中的常量获取

```php
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
            ],
            /*哨兵 主服务器缓存  可选 file缓存 或者apcu缓存*/
            'cache' => [
                "driver" => "file",
                "cacheName" => "/dev/shm/sentinel.php",
                "expire" => 10
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

```

## 使用

```php
$cache = new Cache();
/**
$type 是可以指定一个 CacheStore里的名称或者是一个 `CacheProvide`实现
*/
$cache->store($type)
/**
获取一个Key ，可以指定一个默认值，如果无法命中缓存则返回默认值
*/
$cache->get($key,$defaultValue);

/**
设置缓存
*/
$cache->set($key,$val,$ttl);
/*
删除一个缓存
*/
$cache->delete($key)
/*
清楚所有缓存
*/
$cache->clear();
/**
获取多个Key的值，传递一个数组
*/
$cache->getMultiple(array $keys);
/*
批量设置缓存 传递一个关联数组，数组键值为缓存键值，数组内容为缓存内容
*/
$cache->setMultiple($values,$ttl);
/*
批量删除缓存，传递一个缓存键值的数组
*/
$cache->deleteMultiple(array $keys);
/*
 返回一个key是否存在
*/
$cache->has($key);
```


## 静态方法

提供了一个 `CacheUtil`类提供静态方法设置缓存

```php
CacheUtil::instance($type)->get("key","default")
```

方法同上