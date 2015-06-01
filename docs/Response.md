# Response

本对象主要用于像浏览器输出相关内容，包括状态吗、header、以及其他的模板等数据。  

### json()
输出json格式的内容  
**签名**  
`json(mixed  $arr, boolean  $out = true) : string`  
**参数**  
mixed 	$arr 	要输出的内容   
boolean 	$out 	是否输出 默认为true 设置为false的时候返回编码好的数据  
**示例**

### jsonp()
输出jsonp格式的内容，回调函数不指定时会自动根据配置文件获取回调函数名  
**签名**  
`jsonp(mixed  $arr, string $callback, boolean  $out = true) : string`  
**参数**  
mixed 	$arr 	要输出的内容   
mixed 	$callback 	回调函数名,不指定时会自动根据配置文件获取回调函数名
boolean 	$out 	是否输出 默认为true 设置为false的时候返回编码好的数据  
**示例**  

### setHeader()
设置请求头信息  
**签名**  
`setHeader(  $key,   $val)`  
**参数**  
string $key 键名 header的名字 如 Content-Type  
string $val 键值 指定的header值 如 text/plain  
**示例**  

### getHeader()
获取设置的请求头信息  
**签名**  
`getHeader(string  $key = null) : \array/string`  
**参数**  
string 	$key 	键值header的名字   
**示例**  

### sendHeader()
发送请求头，向浏览器发送设置的请求头信息  
**签名**   
`sendHeader() `  

**示例**  

### send()
发送header加发送一段内容，如果不指定参数，则本方法则相当于 sendHeader方法  
**签名**  
`send(mixed  $str = null) `  

**参数**   
mixed 	$str 发送一段内容,如果内容是数组则会调用json发送，如果为空则本方法相当于sendHeader  

### setStatus()
设置HTTP状态码,默认不设置系统会自动设置200  
**签名**  
`setStatus(mixed  $status = null) : array`
**参数**  
mixed 	$status 状态码   
**示例**  

### getStatus()
获取状态码  
**签名**  
`getStatus() : integer`  
**示例**  

### file()
向客户端发送一个文件，下载文件，输出附件  
**签名**  
`file(string  $file, string  $name = '', boolean  $isdata = false) `  
**参数**  
string 	$file 	文件地址或者一段文字数据，当为文字数据时 isdata必须设置为true  
string 	$name 	下载的文件名  
boolean 	$isdata 	下载的是文件还是一段字符数据 默认是false 为文件   
**示例**  

### setCookie()
设置cookie  
**签名**  
`setCookie(string  $key, string  $name) `  
**参数**  
string 	$key 	cookie键名  
string 	$name 	cookie值  
其他的cooke参数，直接使用系统设置中的参数  
**示例**  

### clearCookie()
删除指定名字的cookie，如果不指定参数那么就会清空所有cookie  
**签名**  
`clearCookie(string  $key) `  
**参数**   
string/null 	$key 	cookie键名  
**示例**   

### redirect()
跳转  
**签名**  
`redirect(string  $url, integer  $status = 302) `  
**参数**  
string 	$url 	跳转的地址   
integer 	$status 	跳转状态码   
**示例**  

### render()
输出模板信息，输出指定的模板和指定的数据解析后的内容。  

**签名**  
`render(string  $tpl, array  $data = array(), integer  $status = null) `  
**参数**  
string 	$tpl 	模板名称  
array 	$data 	传递给模板的数据   
integer 	$status 	状态，系统默认的状态码是200   

**示例**  

### extend()

用于扩展Response类使用,可以使用该方法扩展Response实例的属性和方法

`extend(mixed $pro,$data=null)`  

`$res->extend('name','extend name')`  
`$res->extend(['name'=>'extend name'])`  

`$res->extend('show',function(){ echo $this->name;})`   
`$res->show()`//即可调用上述方法

`$res->extend('showInfo',function($name){ echo $name;})`  
`$res->showInfo('123')` // out:123   

*创建的匿名函数会被自动绑定到当前的实例中，所以在匿名函数中可以直接使用$this*