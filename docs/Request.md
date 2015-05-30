# \Vitex\Core\Request

request对象中保存了一个请求中各种用到的客户端信息以及客户端传递的各种数据信息的warp类。
此对象是一个单例对象。    
此类的实例会被当做第一个参数传递给所有的请求方法以及请求中间件。 
在中间件的二次开发中我们可以通过给此对象的实例赋值来把相应的处理数据传递给请求处理函数。

## 获取当前对象

### 请求处理函数
在请求处理函数中，Vitex总是会把此对象的一个实例传递给请求处理函数   
`
	$vitex->get('/',function($req){
	})
`  
上文中的 $req就为request的实例。
### 自定义类文件
request对象为一个单例对象，在我们的自定义文件中我们可以直接获取：  
`$req = \Vitex\Core\Request::getInstance();`

## 对象的属性

此对象的应用主要为读取其中的属性，包括用户传递来的数据。  

### query

query属性中保存了$_GET变量中的数据  

`$req->query->name`  或者  
`$req->query['name']` 的形式获取相应的数据

### post

post属性中保存了相应的用户post来的数据信息。

`$req->post->name` 或者  
`$req->post['name']`

### params
URL段中的识别字段

`$vitex->get('/user/:id',function($req){})`   
`:id`是一个匹配段，此段可以匹配非`/`的内容
可以使用 `$req->params->id` 调用 该字段的内容  
如 当我们访问 /user/1 时  `$req->params->id`（`$req->params['id']`） 会返回 `1`  

`
$vitex->get('/user/:id/:status',function($req){
	echo $req->params->id;//  
	echo $req->params->status;//  
});
`

### file
	$_FILES的封装

### ip
来源IP

### hostname
来源HOST

### protocol
http协议  http/https

### referrer/referer
来源页面

### secure
是否为安全协议 https

### isAjax/isXhr
同 isAjax方法，判断是不是XHR的请求

### path
当前请求的URL路径，【get】中u重写或者pathinfo中的值。也就是说路由使用的值

### get()
获取环境变量的内容，主要是 $_SERVER变量的内容  
`get(string  $key) `
string 	$key 	键值

### isAjax()
判断请求是不是XHR请求  
`isAjax() : boolean`

## 其他中间件修改增加的属性方法

### getData()

传递一个包含键值的数组获取以该键值为表单名的数据   
`getData(array $arr)`

$arr 包含表单名的一个数组

### extend()

用于扩展Request类使用,可以使用该方法扩展Request实例的属性和方法

`extend(mixed $pro,$data=null)`  

`$req->extend('name','extend name')`  
`$req->extend(['name'=>'extend name'])`  

`$req->extend('show',function($obj){ echo $obj->name;})`   
`$req->show()`//即可调用上述方法

`$req->extend('showInfo',function($obj,$name){ echo $name;})`  
`$req->showInfo('123')` // out:123   

**注意** 扩展方法时,方法的第一个参数总是 $req 的实例,此参数会在执行时默认添加,不需要手动添加此参数.

*PHP没有办法直接动态扩展实例的方法,因此此处相当于变相实现了扩展*