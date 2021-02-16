# 简介

Vitex是一个基于php7.0+的微型Restful的框架。
本框架严格区分命名空间，使用闭包匿名函数来绑定不同的请求。
通过使用应用中间件和路由中间件来实现通用的一些功能。   
Vitex中没有大量的各种工具、类库，如果你需要大量的工具，请在 packagist中查找优秀的开源项目。    
我们建议您使用Composer来管理您的程序，使用composer您可以安装大量非常优秀的类库和工具。   

Vitex尤其适合编写API型的应用，通过灵活的路由管理可以让你快乐的编程。

正如下面所示：  

	$vitex->get('/user/:id',function($req,$res){
		$res->json($req->params->id);
	})
	->post('/user/:id',function($req,$res){
		$res->json($req->body);
	})
	->delete('/user/:id',function(){
		//删除
	});