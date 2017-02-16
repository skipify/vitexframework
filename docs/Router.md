# 路由

## Overview

​	

	Vitex中使用了注册路由的机制，也就是只有明确指定的路由才可以被访问，这样的好处很明显就是不会产生一些未知的请求问题；但是不足也很明显，使用起来相对比较繁琐一些。

	路由严格分可以分为两种，一种是普通的路由，另一种是路由级别的中间件。  

**普通路由**  

路由主要按照不同的请求方法分为不同的路由：

	- GET  

	- POST  

	- DELETE  

	- PUT  

	- OPTIONS  

	- ALL  

当然当前请求为POST的时候还可以使用 表单中传递 __METHOD 参数来指定一个自定义的路由，使用 `map`方法来注册路由  

***注意** 如果是命令行模式的路由，当前支持以下几种形式：
```
   php index.php /home/user
   php index.php /user/edit?id=1
   php index.php /user/edit id=1
```

**路由中间件**  

路由中间件为一个特殊的路由类型，相当于Vitex注册了一个自己的自定义路由，相当于注册了一个名为 `using`的自定义路由当做路由中间件。

	 - INVOKE  

## example

``` 
$vitex->get('/',function($req,$res){
		$res->json(['name'=>'Vitex']);
	});
	$vitex->get('/user',function($req,$res) use ($vitex){
		echo $vitex->getConfig('debug') ? 'Debug' : 'Prod';
	});
	$vitex->map('get,post',function(){
		//同时注册 get,post两种方法
	})
	等价于
	$vitex->map(['get','post'],function(){
	})
	$vitex->post('/',function(){});
	$vitex->delete('/',function(){});
	$vitex->put('/',function(){});
	$vitex->options('/',function(){});
```

## 路由匹配

使用路由注册方法(get/post/put/delete/options/all),注册的路由第一个参数都为路由匹配字段，此字段为一个字符串字段。  

当有请求的时候会使用请求的URL与当前字段设置的匹配情况来路由到相应的处理方法。

**路由匹配**字段  

注册路由的`第一个`参数总为匹配参数，此参数为一个字符串，其中可以使用 `/`分割不同的URL（不包含querystring字符串）段（类似于常规请求的目录）。

*匹配参数*  

还可以使用 `:`开头的字母字符串来表示一段`匹配参数`，任意字符串匹配([^/]+)。  

	`$vitex->get('/user/:id',function(){}) // 可以匹配 /user/1  /user/add 等  `

	`$vitex->get('/article-:id,function(){}') // article-1`

使用 $req->params 对象可以获取 `匹配参数`的值  

``` 
	$vitex->get('/:id',function($req){
		echo $req->params->id; // $req->params['id'];
	})
```

*限制匹配参数*   

匹配参数可以使用格式限制来限制匹配的内容格式

``` 
$vitex->get('/:id@digit',function($req){
		echo $req->params->id;
})
```

如上的url中id段只可以匹配0-9的数字，其他的值则不会匹配   

格式为 `:segment@type`  @后面的值为匹配的格式，内置了4中基本类型：   

- `digit`  只匹配数字  
- `alpha`  匹配大小写字母   
- `float`  只匹配浮点数字
- `alphadigit` 匹配大小写字母和数字

如果需要**自定义**的格式字符串可以使用  

``` 
	$vitex->setRouteRegexp('username','[a-z0-9]{3,5}');
	$vitex->get('/:user@username',function(){
	
	})
```

	可以匹配 /asd  /asdf

	不可以匹配 /as43  /as

*任意匹配*  

	`$vitex->get('/user*',function(){}) // 可以匹配 /user /user123 /userasdjf `

`*`号可以匹配除了 `/`之外的0-多个的字符([^\/]*)  

	`$vitex->get(/user?,function(){}) // 可以匹配 /user1 /user2 /user /users`

`?` 可以匹配一个或者零个非`/`字符  

*匹配参数为正则表达式*  

``` 
$vitex->get('|^test/[0-9]+$|', function ($req) {
	echo 'test';
});
// /test/1  test/13
```

以 `||` 包裹的内容会被当做一个正则表达式匹配来解析。  

``` 
$vitex->get('|^test/([0-9]+)$|', function ($req) {
	echo $req->params[0];
	// /test/1  输出 1
});
//可以对要匹配的内容进行分组，分组后的结果可以使用 $req->params获取
$vitex->get('|^test/(?<id>[0-9]+)$|', function ($req) {
	echo $req->params->id;
	//  /test/5  输出 5
});
//可以对分组起名
```

> 注意：正则表达式形式的写法必须要添加分组名  

例如：

```

$vitex->group("user","user")  //指定 /user开头的分组到user router文件

$vitex->group("group/user","usergroup")  // 指定 /group/user 开头的分组到 usergroup分组文件

user.php (router文件)中使用正则匹配则
$vitex->get("|^user/get-[0-9]+\.html$|","User@info")  //  相当于访问  /user/get-1.html
```


*可选匹配*

```
/foo/:bar[/:baz]

可以匹配  /foo/123   /foo/abc/123  /foo/a/b

/foo/:bar@alpha[/:baz@digit]  

可以匹配 /foo/name/1   /foo/user/3   /foo/user

```

[/] 为可选url段的形式 [/:baz] **不可以**写成[:baz]


**处理函数**  

注册路由的`最后一个`参数总为路由处理方法，此方法为一个 callable的参数，通过此方法来处理请求信息。

``` 
$vitex->get('/',function($req,$res,$next){

})->post('/',function($req,$res,$next){
  
});
```

*$req*  

此参数为 [Request](Request.html)类的实例，此对象中保存了各种请求数据。  

我们可以使用此参数本来获取大多数我们需要的内容，同时编写中间件的过程中给此参数复制可以传递给后续的处理方法。   

*$res*  

此参数为 [Response](Response.html)类的实例，此对象中包含了一些输出相关的方法和属性。  

*$next*  

此参数为一个callable的参数，执行此函数可以让路由匹配继续进行`下一次匹配`，在路由中间件和多种不同方法（method）注册相同的匹配参数时非常重要。默认情况下路由匹配在匹配到一个（按注册顺序）之后就会停止匹配，使用该参数函数可以让路由匹配继续进行。   

**处理函数为类方法**  

为了方便大型项目的模块化开发，vitex也支持模块化的组织处理函数。如果默认使用 Init.php生成应用，则会有一个 `Controller` 的文件夹存在，此文件夹只要用于存放处理路由的方法。  

此种调用情况下，系统使用 `类名@方法名`的方式来调用，如果没有指定方法名则会默认把请求匹配的请求method作为方法名。

	$vitex->get('/','Index') 

此时当访问 `/`时会自动调用 `Controller/Index.php`中的`get`方法   

当然也可以完全限定命名空间

	$vitex->get('/','\App\Controller\Index');   

指定方法名

	$vitex->get('/','Index@index') 

此时当访问 `/`时会自动调用 `Controller/Index.php`中的`index`方法   

**处理类的定义**   

处理函数的类定义需要继承vitex的Controller类

``` 
	class User extends \vitex\Controller
	{
		public function get()
		{
			//在方法里可以直接用
			//$this->req 调用 $req对象
			//$this->res 调用 $res对象
			//$this->vitex  调用 Vitex实例 等同于  \vitex\Vitex::getInstance(); 
			//此外 $vitex中的其他方法也可以直接使用例如 ：
			//$this->getConfig() === $this->vitex->getConfig();
		}
	}
```

## 自定义404页面

``` 
$vitex->notFound(function($req,$res,$next){
  
});
//404页面与普通路由一致
```

## 获取当前匹配的控制器和方法

```
$vitex->route->router->getRouteMethodClass(); 
Array
(
    [0] => \app\controller\User  控制器
    [1] => get   方法名
)
```