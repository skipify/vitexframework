#View
视图类，用于模板的展示以及模板数据的设置.  
当前类在系统启用了 view之后会自动实例化并且作为$vitex的一个属性存在。  
当调用Vitex对象实例的 view方法后（`$vitex->view()`），可以使用 `$vitex->view` 来访问到当前类的实例.  
当然你也可以直接使用 `new \Vitex\View()`来实例化当前类。  

##setStyle()
设置模板风格，可以设置一个风格文件夹，比如 default

**签名**   
`setStyle(string $style)`    
**参数**
string $style 参数    

**示例**   

`$vitex->view->setStyle('default')`


##set()
给模板传递变量,单个或者多个数据设置。  
**签名**  
`set(string  $key, string  $val) `  
**参数**  
string/array 	$key 	键值   
string 	$val 	键名   
**示例**  
`$vitex->view->set('title','mysite')`  
`$vitex->view->set(['title'=>'mysite'])`

##get()
获取数据，获取通过set设置的数据。 
**签名**  
`get(string  $key = null) : mixed`  
**参数**  
string 	$key 键值   
**示例**  
`$vitex->view->get('title')`  

##setTplPath()
设置当前设置的模板所在的路径,比如你的模板放在/home/www/tpl下 那么直接设置给他，  
当前设置的路径会被直接连接到 fetch/display方法所指定的模板名之前。  
默认的模板路径是系统配置 `templates.path`指定的路径

**签名**  
`setTplPath() : object`  
**参数**  
string $path 指定的模板存放路径  
**示例**  
`$vitex->view->setTplPath('/home/www/tpl')`

##getTplPath()
获取当前设置的模板所在的路径

**签名**  
`getTplPath() : string`  
**示例**  
`$path = $vitex->view->getTplPath()`

##template()
通过传递模板的名字自动根据配置生成模板的绝对路径.
此方法使用setTplPath指定的路径来构造绝对路径  

**签名**  
`template( $tpl) `  
**参数**  
string $tpl 模板的名字   
**示例**  
`$vite->view->template('index')` // /home/www/tpl/index.html

##fetch()
通过传递的模板和数据，返回解析的模板数据。  
**签名**  
`fetch(string  $tplname, array  $data = array(),   $merge = true) : string`  
**参数**  
string 	$tplname 模板名称   
array 	$data 	数据,除了在这里可以指定数据之外，还可以使用 set来设定数据，  
				或者使用Response对象的set方法设定数据
				比如指定 ['name'=>'Vitex'],在模板中可以直接使用 $name 调用 Vitex内容    
bool	$merge 	是否要合并数据，一般不需要指定该参数   
**示例**  
`$vitex->view->fetch('index',['title'=>'mysite'])`  

>*注意* 此方法在 Vitex对象和Response对象中均有封装，他们封装的名字为 render,  
但是他们的表现与display表现一致，直接输出了内容
`$vitex->render('index',[])`  
`$vitex->res->render('index',[])`


##display()
作用与fetch方法类似，但是此函数会直接输出相应的数据。相当于：  
`echo $this->fetch(...)`

##render()
模板中嵌套子模板使用，支持多级的嵌套
**签名**  
`render(string  $tplname, array  $data = array(),   $merge = false) : string`    
**参数**
string 	$tplname 模板名称   
array 	$data 	子模板中使用的 除了继承主(父)模板的数据之外，还可以在这里指定额外的数据，使用方式如 `fetch`方法   
bool	$merge 	是否要合并数据，一般不需要指定该参数   

如  `index.html`模板中  使用  `$this->render('head')` 可以把 `head.html`的内容嵌套到`index.html`中显示  
如上 head.html中还可以嵌套 其他的子模板 如  `nav.html`;在head.html中使用 `$this->render('nav')`