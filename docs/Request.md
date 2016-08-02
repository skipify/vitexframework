# \vitex\core\Request

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

`$req = \vitex\core\Request::getInstance();`

## 对象的属性

此对象的应用主要为读取其中的属性，包括用户传递来的数据。  

### query

query属性中保存了$_GET变量中的数据,0.9.0之后的版本使用此属性获得的数据都会自动被addslahes转义,如果需要取消转义需要使用此类的setNotFilter方法来取消转义 

`$req->query->name`  或者  

`$req->query['name']` 的形式获取相应的数据

### body

body属性中保存了相应的用户post来的数据信息,0.9.0之后的版本使用此属性获得的数据都会自动被addslahes转义 ,如果需要取消转义需要使用此类的setNotFilter方法来取消转义 

`$req->body->name` 或者  

`$req->body['name']`

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

### getEnv()

获取环境变量的内容，主要是 $_SERVER变量的内容  

`getEnv(string  $key) `

string 	$key 	键值

### isAjax()

判断请求是不是XHR请求  

`isAjax() : boolean`

### isGet()

判断请求是不是get方法请求

`isGet() : boolean`

### isPost()

判断请求是不是post方法请求

`isPost() : boolean`

### isPut()

判断请求是不是put方法请求

`isPut() : boolean`

### isPatch()

判断请求是不是patch方法请求

`isPatch() : boolean`

### isDelete()

判断请求是不是delete方法请求

`isDelete() : boolean`

### isHead()

判断请求是不是head方法请求

`isHead() : boolean`

### isOptions()
判断请求是不是options方法请求

`isOptions() : boolean`

## 其他中间件修改增加的属性方法

### getData()

传递一个包含键值的数组获取以该键值为表单名的数据,0.9.0之后的版本此方法增加了第二个可选参数,可以指定获取的数据是否经过过滤,默认是addslashes转义   
过滤方式可选值请参考ext/Filter类

`getData(array $arr,$filter)`

$arr 包含表单名的一个数组

### get()

获取一个请求来的值，获取的顺序为 params > query > body 即优先从url中获取段的信息，其次获取$_GET变量的信息最后获取$__POST的信息，如果都不存在则会返回第二个参数设置的默认值,可以指定第三个参数作为过滤参数(参考ext/Filter)

**注意** 此方法获取的post的值不包含 php://input形式的值

**签名**

`get(string $key,mixed $defult,$filter=addslashes)`

**示例**

`$this->req->get("name","vitex")`

### setNotFilter
默认的 $req->body  $req->query的数据都是经过 addslashes处理的,如果需要取消这种处理请调用此方法

```
$this->req->setNotFilter();
```

### getBody()

从$_POST中获取值,如果不存在则会返回第二个参数设置的默认值,可以指定第三个参数作为过滤参数(参考ext/Filter)

**注意** 此方法获取的post的值不包含 php://input形式的值

**签名**

`getBody(string $key,mixed $defult,$filter=addslashes)`

**示例**

`$this->req->getBody("name","vitex")`

### getQuery()

从$_GET中获取值,如果不存在则会返回第二个参数设置的默认值,可以指定第三个参数作为过滤参数(参考ext/Filter)

**签名**

`getQuery(string $key,mixed $defult,$filter=addslashes)`

**示例**

`$this->req->getQuery("name","vitex")`


### getParam()

从URL中获取值,如果不存在则会返回第二个参数设置的默认值,可以指定第三个参数作为过滤参数(参考ext/Filter).
此方法不会自动过滤数据,获取到的是原始的值,建议此类数据应该在路由中增加限制,防止非法数据请求路由到当前方法处理

**签名**

`getQuery(string $key,mixed $defult,$filter=addslashes)`

**示例**

`$this->req->getParam("name","vitex")`

### extend()

用于扩展Request类使用,可以使用该方法扩展Request实例的属性和方法

`extend(mixed $pro,$data=null)`  

`$req->extend('name','extend name')`  

`$req->extend(['name'=>'extend name'])`  

`$req->extend('show',function(){ echo $this->name;})`   

`$req->show()`//即可调用上述方法

`$req->extend('showInfo',function($name){ echo $name;})`  

`$req->showInfo('123')` // out:123   

*创建的匿名函数会被自动绑定到当前的实例中，所以在匿名函数中可以直接使用$this*