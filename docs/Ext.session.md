# SESSION中间件

此中间没有默认加载，如果需要使用请手工加载： 

`$vitex->using(new\vitex\middleware\Session());`

## 使用方式

1. 匿名函数的应用
   
   	$vitex->get(‘/‘,function($req){
   
   		$req->session->name = “vitex"；
   
   		echo $req->session->name;
   
   	});
   
2. 控制器的应用
   
   ​       …...

		public function get()

		{

			$this->req->session->name = 'vitex';

			echo $this->req->session->name;

		}

		…...

## 方法

### get

获取session

`$req->session->get("name")`

### set

设置session

`$req->session->set("name","vitex”);`

`$req->session->set(["name"=>”vitex"]);`