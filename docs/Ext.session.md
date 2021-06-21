# SESSION中间件


session保存目前支持三种，一种是自定义的文件存储file，一种是默认的PHP原生存储 native,还可以设置为redis memcache的存储


配置项

```
'session'=>[
    'driver' => 'native',
        /**
     * 会话存活期  分钟
     */
    'lifetime' => 15,
    /**
     * 文件保存配置的时候的路径
     */
    'path' => '',
        /**
     * redis memcache数据缓存时候的实例
     */
    'instance' => null
]

```

**注意** 如果设置 为 `cache`的时候 `driver` 需要传递一个连接实例，缓存实例必须要支持 `set`,`get`,`delete`方法 或者是个 callable的方法返回一个cache实例
例如 memcache实例或者redis实例
        
        如果设置为 `file`的时候需要设置 `session.file.path`为保存的文件目录

此中间没有默认加载，如果需要使用请手工加载： 

`$vitex->using(new\vitex\middleware\Session());`

## 使用方式

1. 匿名函数的应用
   
   	$vitex->get(‘/‘,function($req){
   
   		$req->session->name = “vitex"；
   
   		echo $req->session->name;
   
   	});
   
2. 控制器的应用
```
public function get()
{
	$this->req->session->name = 'vitex';
	echo $this->req->session->name;
}
```

## 方法

### get

获取session

`$req->session->get("name")`

### set

设置session

`$req->session->set("name","vitex”);`

`$req->session->set(["name"=>”vitex"]);`