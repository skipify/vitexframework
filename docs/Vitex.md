# Vitex

### 实例

	

	`$vitex = \vitex\Vitex::getInstance()`

创建vitex的实例。

### 配置

setConfig/getConfig  

设置/获取当前的配置   

	`$vitex->setConfig("templates.path","./templates")`  

	`$vitex->setConfig(['templates.path'=>'./templates'])`

配置如下   

``` 
'debug'               => false, //默认关闭调试   
'templates.path'     => './templates',   
'templates.ext'      => '.html',
'view'               => '\vitex\View', 
'callback' => 'callback',  
'router.group_path'   => '',   
'router.compatible'  => false, //路由兼容模式，不支持pathinfo的路由开启  
'methodoverride.key' => '__METHOD', //url request method 重写的key  
'cookies.encrypt'    => true, //是否启用cookie加密  默认启用
'cookies.lifetime'   => '20 minutes',  
'cookies.path'       => '/',  
'cookies.domain'     => null,  
'cookies.secure'     => false,  
'cookies.httponly'   => false,  
'cookies.secret_key' => 'Vitex is a micro restfull framework',  
```

### setConfig

设置配置

**签名**   

`setConfig(string/array  $name, string/null  $val = null) `  

**参数**  

string/array 	$name 	键值/数组配置/配置文件名   

string/null 	$val 	值   

**示例**  

`$this->setConfig('debug',false)`  

`$this->setConfig(['debug'=>false])`  

`$this->setConfig('/home/www/test/config.php')`  

当配置文件为文件名时，该文件必须要返回一个数组：

``` 
<?php  
    return ['debug'=>true];
```

### getConfig

获取配置   

**签名**   

`getConfig(string  $name) : mixed`   

**参数**  

string 	$name 	配置名  

**示例**  

`$this->getConfig('debug')`  

### getInstance

这是一个静态方法，用于获取Vitex的实例（单例对象）。  

`$vitex = \vitex\Vitex::getInstance()`  

### init

用于初始化一个应用，通过此方法可以设置APP路径、名字，批量设置一些配置文件以及一些预加载的中间件。 

主要设置了应用所在的路径，设置好自动加载以及路由分组的名称   

**签名**  

`init(string $app, string $dir,array setting , array middleware)`  

**参数**  

string $app 应用的名称（同时也应该是这个应用的文件夹名）  

string $dir 应用所在的路径  

array setting 批量设置一些配置类似于 setConfig方法  

array middleware 批量加载预处理中间件  

**示例**  

`$vitex->init('app', dirname(__DIR__));`  //直接在webroot目录初始化应用  

### setAppMap

用于设置多个应用时链接访问的映射关系，此映射关系可以把应用绑定到域名或者一个访问目录；域名如`test.com,m.test.com`;目录如： `/user/verify,/user/check`中的 `user`段。

多应用时的映射方式，当您一个大型的项目需要多个应用配合时需要使用此种方式更好的组织代码

> 例如一个后台管理项目分为 前台以及管理员的后台，此时可以创建两个应用，单独负责自己的事宜域名的映射规则高于默认的级别

**签名**

`setAppMap(array $map,string $domain="default"):object`

**参数**

array $map 映射关系，关联数组，此数组对少包含两个参数，最多4个，四个参数会对照传递个`init`方法

string $domain 当前映射关系要绑定的域名，默认不指定表示所有域名都可以使用

**示例**

``` 
$vitex->setAppMap([
  ["app",dirname(__DIR__)],
  "user" => ["user",dirname(__DIR__)]
]);
```

以上示例表示绑定了一个`所有域名`通用的规则，当要访问的链接path第一段是user时会自动调用user的应用，其余时则会调用第一个没有指定键名的app应用。

``` 
$vitex->setAppMap([
  ["app",dirname(__DIR__)]
])->setAppMap([
  ["user",dirname("__DIR__")],
  "api" => ["api",dirname(__DIR)]
],"user.test.com");
```

如上绑定了一个默认所有域名的规则，另外还给user.test.com域名单独绑定了两个规则，比如我访问 www.test.com时的请求都会被路由到app应用；如果访问user.test.com 都会路由到第二次绑定的路由中，如果第一段是api则会被路由到api应用，如果第一段不是api则都会路由到user应用。

### multiInit

初始化多应用的项目，具体功能可以参考`init`方法，但是此方法会根据`setAppMap`的规则进行路由的转换。

**签名**

`multiInit():string`

**返回值**

此方法会返回根据setAppMap设置的路由规则中被路由到的应用名。

**示例**

``` 
//注册应用映射规则参考setAppMap方法
$app = $this->multiInit();

//下面可以根据$app的不同加载不同的信息，如不同的中间件、不同的路由规则、不同的路由分组
swicth($app) {
  case "user":
  	$vitex->get("/","User@info");
  break;
  case "app":
  	$vitex->group("/","Welcome")
  break;
  case "api"
  	$vitex->group("/","Index");
  break;
}
```

这是一个多应用存在时的一种解决办法，当然还有直接分离的解决办法，可以参考[多应用](Multiapp.md)文档。

### hook

注册钩子函数，注册的钩子可以使用appHook方法来触发钩子函数执行  

系统有两个保留的钩子，一个是before.router在开始路由之前after.router在路由结束滞后  

**签名**  

	`hook(string  $name, callable  $call, integer  $priority = 100) : object`  

**参数**  

string 	$name 	钩子名称  

callable 	$call 	可执行的方法  

integer 	$priority 	执行的优先级，数字越大越提前   

**示例**  

	`$vitex->hook('before.router',function(){echo 'before route';})`  

	`$vitex->hook('login',function(){echo 'islogin';})`

### applyHook

执行指定的钩子名称所注册的所有方法  

**签名**  

	`applyHook(string  $name) `  

**参数**  

string 	$name 	钩子名称  

**示例**  

	`$vitex->applyHook('login')` 执行注册到login钩子的所有方法

### getHooks

获取指定的钩子名称所注册的所有方法或者如果不指定名称返回所有的注册方法  

**签名**  

	`getHooks(string  $name = null) : array`  

**参数**  

string 	$name 	钩子的名字  

**示例**  

	

	`$vitex->getHooks('login')`  

	`$vitex->getHooks()`  返回所有注册的函数（关联数组）

### view

启用视图功能，系统会自动实例化View类，使之支持模板

**签名**  

	`view() : mixed`  

**示例**  

	`$vitex->view()`  

### render

根据设定的数据直接输出指定的模板解析后的html数据  

**签名**  

	`render(string  $tpl, array  $data = array(), integer  $status = null) `  

**参数**  

string 	$tpl 	模板名称

array 	$data 	传递给模板的数据

integer 	$status 状态码，默认会输出200  

**示例**  

	`$vitex->render('index',['title'=>'my site'])`

### using

注册中间件,中间件分为两种级别，一种是应用级别的中间件，一种是路由级别的中间件。  

`预处理中间件`是在路由开始之前执行的一系列指定的方法。  

预处理中间件 会按照和注册顺序相反的顺序逐个执行，详见中间件的开发规范。 

`路由中间件` 是一种特殊的路由机制，他的执行和路由行为一致，执行顺序也和路由的注册执行顺序一致。   

**签名**  

	`using($pattern, $call) : object`  

**参数**  

mixed $pattern 路由中间件时此参数为URL匹配段，应用级中间件时此参数为中间件的一个实例  

mixed $call 要执行的中间件处理方法或者null(应用中间件时)    

**示例**  

	`$vitex->using(new \vitex\middleware\Session())`  

	所有的预处理中间件(应用级中间件)必须要继承自 \vitex\middleware类   

``` 
`$vitex->using('/',function($req,$res,$next){echo $req->query->name;})`  
每一个中间件的方法都可以接受三个参数，request对象，response对象，一个执行下
一个匹配路由的next方法
```

### group

注册路由分组

**签名**  

	`group(string  $pattern, string  $class) : object`  

**参数**  

string 	$pattern 	分组标识 url的一部分

string 	$class 	分组对应的类的名字  

**示例**  

	`$vitex->group('/user','user')` 把所有 /user的请求都重定向到 user文件中  

	user文件的路径可以通过改变 router.group_path重新设定

### get/post/put/delete/options/all

按照不同的请求方式注册请求处理方法  

当多于一个callable的参数时，最后一个callable当做处理请求的“请求处理器”   

其余的callable都会当做匹配当前URL时执行的 中间件，而且中间件的执行要早于“请求处理器”的执行  

详情请参考[路由](Router.html)

**签名**  

	`get()`  

**参数**  

string/array 	$pattern 	匹配的url规则,多个匹配规则时可以传递一个数组

[callable/array 可选参数，可以指定一个或者多个(数组)中间件来执行，相当于在当前pattern上调用using来注册中间件]

callable 	$call 	匹配后执行的方法

**示例**  

	

	`$vitex->get('/',function(){echo 'index';})`  

	`$vitex->get('/user',function() use ($vitex){ $vitex->render('user');})`

	可以使用:name的方式设置一个变量  

	`$vitex->post('/user/:id',function($req){ echo $req->params->id;})`  

	`$vitex->get('/article-:id',function(){echo $req->params->id;})`



### map

	

	如 get/post 等注册函数，此方法可以一次注册多个不同请求方式的函数

**签名**  

	`map()`  

**参数**  

string/array  $method 需要注册的请求名 如 get,post 或者 ['get','post'] 可以指定多个

string/array 	$pattern 	匹配的url规则,多个匹配规则时可以传递一个数组

[callable/array 可选参数，可以指定一个或者多个(数组)中间件来执行，相当于在当前pattern上调用using来注册中间件]

callable 	$call 	匹配后执行的方法

**示例**  

	`$vitex->map('get/post',function(){echo 'map';})`

### notFound

404页面，如果不指定$call则会触发执行默认或者已经设定(如果设定过)的notfound方法   

**签名**  

	`notFound()`  

**参数**  

callable 	$call 	无法路由时执行的方法  

**示例**  

	`$vitex->notFound(function(){echo 'Not Found';})`  

### run

执行Vitex框架