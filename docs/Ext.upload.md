# Upload 文件上传

Upload类是一个专门用于上传的工具，此类是一个继承自\Vitex\Middleware的中间件程序；他可以当做一个中间件来使用，也可以单独在处理程序中当做普通的类来调用。  

## 当做中间件使用
中间件形式的调用，所有的配置都必须在构造函数中指定  

	$vitex->using(new \Vitex\Ext\Upload([
		'ext' => 'jpg',  
		'fieldname' => 'filename',  
		'dest' => '/home/www/default'  
	]));

## 普通类使用 
	
	$upload = new \Vitex\Ext\Upload([
		'ext' => 'jpg',  
		'fieldname' => 'filename',  
		'dest' => '/home/www/default'  
	]);  
	$upload->setDest('/homewww/default2');  

## 配置 

    ext：* //允许上传的扩展名，多个请使用 |分割 如 jpg|png   
    rename： function(){return $this->rename();}, 
    		 //如果上传多张图片请使用改方法动态生成新名称   
    		 // 可以指定一个匿名函数来生成新的文件名    
    		 `function($field,$filename){ return 'newname';}`  
    		 //第一个是上传的表单名，第二个为文件名   
    fieldname： '', 上传接受的字段名，如前台表单字段名为file, 不指定会读取所有的$_FILES   
    dest     ： '' 指定上传文件要保存的目录地址

## 返回值 
	
	array 一个二维数组，每个元素为一张图片  
	
	中间件形式的调用返回值使用  `$req->upload` 错误信息使用 `$req->uploadError`  
	
	普通调用形式 返回值使用  `call`方法的返回值  错误信息使用 `$upload->getError()`  

    'filedname'  前台字段名   
    'originalname' 文件的原始名   
    'name'         当前文件的名字   
    'mimetype'     mime类型   
    'path'         路径   
    'ext'          扩展名   
    'size'         文件大小  


### 中间件
`$ret = $req->upload`   
中间件形式的调用会把调用结果赋值给Request对象的 upload属性 

### 普通类
`$ret = $req->call();`

## API  


### setDest 

设置文件的存储路径

**参数**  

string $path  存储的路径

### getError

获取当前的错误信息，如果上传中存在错误  

### call

执行调用上传