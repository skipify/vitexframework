# 多应用项目

多应用时的映射方式，当您一个大型的项目需要多个应用配合时需要使用此种方式更好的组织代码

``` 
 例如一个后台管理项目分为 前台以及管理员的后台，此时可以创建两个应用，单独负责自己的事宜
```

## 第一种多应用项目

回顾单应用的入口设置

``` 
require '../vendor/autoload.php';
$vitex = \vitex\Vitex::getInstance();
const WEBROOT = __DIR__;
$vitex->init('app', dirname(__DIR__));
```

以上代码初始化了一个名为app的应用，就如使用自动创建工具创建的那样，这里的所有的路由都交给了app应用，如果我有两个应用怎么办？如现在我要做一个前后台的管理项目，后台负责管理，前台负责给应用提供公开的接口：

1. admin
2. app

此时我需要使用一个域名访问，当访问不同的目录时自动调取相应的应用来处理路由

``` 
初始化多应用的格式：
$vitex->setAppMap();//设置映射规则
$appname = $vitex->multiInit();//启动应用初始化

等价于
$appname = $vitex->setAppMap()->multiInit();

这里$appname是初始化的应用名称
```



``` 
require '../vendor/autoload.php';
$vitex = \vitex\Vitex::getInstance();
const WEBROOT = __DIR__;
//设置应用映射
$vitex->setAppMap([
  ["app",dirname(__DIR__)],//不带键名表示默认路由
  "app" => ["app",dirname(__DIR__)],
  "manage"=>["admin",dirname(__DIR__)]
]);
$vitex->multiInit();
//如上，配置俩应用的配置(配置项后面讲)，当访问  /manage/login 时会自动调取 admin应用的路由，当访问/app/api时会调用app应用的路由，当访问/api时也会调用 app应用的路由
```

大家可以看到 `setAppMap` 方法接受了一个关联数组的参数，此参数的键名为访问URL中的分段如/admin/app中的 admin。每一个元素的数组为 init方法的配置项，如我们开始的时候初始化单个应用的例子。

下面看一个更复杂的，使用了域名和url分段匹配

1. admin
2. member
3. app

如上例子，我使用 admin.test.com 访问后台admin应用 使用 www.test.com访问前台应用,使用www.test.com/member访问会员应用

``` 
require '../vendor/autoload.php';
$vitex = \vitex\Vitex::getInstance();
const WEBROOT = __DIR__;
//设置应用映射
//首先注册一个默认的请求处理方式，对于所有未知的请求都会路由到此处，如果不指定此路由，当有未知路由访问时则会显示404,此处可以设置为默认路由，用于不需要分组的应用来使用
$vitex->setAppMap([
  ["app",dirname(__DIR__)]
])->setAppMap([
  ["app",dirname(__DIR__)],//此项不指定键值表示未知的当前域名路由都指向此处
  "member" => ["member",dirname(__DIR__)]
],"www.test.com")->setAppMap([
  ["admin",dirname(__DIR__)]
],"admin.test.com");


$appname = $vitex->multiInit(); //启动多应用初始化,此值会返回当前初始化的应用名称
```

如上可以看到另一个点，如果不指定配置文件的键名则为一个默认路由，当无法找到匹配的时候都会路由到默认项。

规则如下：

系统会首先按照当前域名查找：

1. 查找到了有当前域名设置的匹配
   1. 查找URL的第一段作为`匹配段`的配置，在当前域名配置项中查找，如果找到就是用此配置指向的应用进行路由，且`匹配段`会在当前的URL匹配中被删除。
   2. 无法找到第一段的匹配段则会查找是否有当前域名的默认配置是否存在，如果有则会使用此配置路由，链接中的第一段不会被删除
2. 没有查到当前域名的设置
   1. 查找URL的第一段作为`匹配段`的配置，在无域名设置的匹配项中查找，如果找到就是用此配置指向的应用进行路由，且`匹配段`会在当前的URL匹配中被删除。
   2. 无法找到第一段的匹配段则会查找是否有当前域名的默认配置是否存在，如果有则会使用此配置路由，链接中的第一段不会被删除

## 第二种方式

除了第一种多路由方式，还有第二种方式，就是直接使用服务器的重写规则来定义，假设的你的一个项目的多个应用布置在同一个服务器，那么我们通过`cts` 初始化了两个不同的应用，app1,app2；入口文件分别为app1.php,app2.php；此时我们所有的规则都可以通过apache或者nginx的重写规则来实现不同的域名或者不同的目录访问不同的入口文件即可，此种方式的优点是两个应用互不影响，完全独立。

如Apache的重写如下：

``` 
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{HTTP_HOST} ^test.com [NC]
RewriteRule ^(.*)$ /app1.php/$1 [QSA,PT,L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{HTTP_HOST} ^test.cn [NC]
RewriteRule ^(.*)$ /app2.php/$1 [QSA,PT,L]

```