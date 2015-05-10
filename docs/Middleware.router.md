# 路由级的中间件
路由级的中间件是指的是*中间件的执行需要匹配当前的请求*，因此他只在匹配路由的时候才会执行；可以看做路由中间件为一个特殊的请求处理函数。
#### 使用

路由中间件可以使用 `using` 注册调用:`$vitex->using('/',function($req,$res,$next){})`   

还可以通过 请求注册时同步注册调用： `$vitex->using('/',function($req,$res,$next){},function($req,$res){});` 此种方式最后一个callable为请求处理函数，中间的都为路由中间件，注册的顺序为参数顺序注册。


路由中间件与请求处理函数一样使用同样的匹配和调用方式；因此，他们的执行是相互关联的，**执行的顺序总是与注册顺序一致**。  

每一个中间件函数都包含3个参数， （$req,$res,$next）:  
- $req object Request对象当前的实例  
- $res object Response对象当前的实例  
- $next callable 一个可执行的函数，此函数的作用是 继续匹配  

####$next详解：  
	路由匹配总是会按照注册顺序来执行，当匹配到第一个匹配的注册时会调用他的处理函数，
	此处理函数执行过程中可以使用 `$next()` 来继续执行匹配，
	如果在中间件中不使用`$next()` 那么就会使得请求结束，不会继续匹配其他处理函数。  

####DEMO：  

	
	$vitex->using('/',function($req,$res,$next){  
		echo 'run router middleware';  
		$next();//通过路由中间件，继续执行下一次匹配  
		//如果不掉用该方法则不会执行输出hello world  
	})  
	->get('/',function($req,$res){  
		echo 'hello world!!';  
	});  
