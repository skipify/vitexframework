# 预处理中间件(应用级中间件)

应用级的中间件在整个框架执行过程中，执行在路由之前(router.before),所有的请求都会执行应用中间件（以下简称中间件）。  
中间件的执行顺序与注册顺序正好相反，最后注册的中间件总是会最先执行，Vitex中有两个预注册的中间件:  
一个是cookie一个是methodoveride，这两个中间件中 cookie**总是**会第一个执行，methodoveride**总是**会最后一个执行。

## Vitex系统中间件

### Cookie
Cookie执行后主要是在Request对象中增加了一个 cookies 的属性，可以通过他获取到cookie的信息  
`$req.cookies.name`
`$req.cookies['name']`

### MethodOveride

用于重写请求的方法，对于部支持 Put Delete这样的方法可以直接重写。  
重写的条件：  
	- 当前请求必须是 post 请求  
	- 当前提交的表单中必须要有一个字段是 __METHOD（可以通过$vitex->setConfig重设名字）的名字，该字段的值为要重写的方法比如 put

### Session

session 中间件没有自动加载，如果需要使用session 可以手动加载该中间件  

`$vitex->using(new \vitex\middleware\Session());`  

Session中间件主要是重新定义了session信息，在Request对象中增加了session属性,  
加载Session后可以使用 `$req->session->name`或`$req->session['name']` 来获取session  
或者 设置session `$req->session->name = 'Vitex';` 或 `$req->session['name'] = 'Vitex';`   

## 中间件开发

所有的中间件都应该继承 \vitex\middleware ，这样才可以使用using方法注册中间件。  

基类\vitex\middleware包含一个子类必须要实现的方法 `call`,此方法供给调用中间件执行时调用。  

包含一个 `runNext` 的 final 方法，此方法为执行下一个中间件的调用函数，如果call方法中不好含`$this->runNext()` 那么系统在执行到该中间件后会自动中断后续中间件的执行。  

>**注意：** 此中间件的中的中断并不会中断请求的执行，只是会阻止其他**早于**当前中间件注册的中间件的执行(执行与注册顺序相反)。


	class Test extends \vitex\middleware
	{

		public function call()
		{

			$this->runNext();//根据条件必须要写
		}
	}

### \vitex\middleware

#### nextMiddleware()
设置下一个要执行的预处理中间件，Vitex用它来实现call的`链式`调用
**参数**  
\vitex\middleware 	$call 	中间件

#### setVitex()

设置当前应用，本方法是Vitex注册中间件时使用，使得所有继承  \vitex\middleware的对象都可以使用`$this->vitex`调用 Vitex
**参数**  
object 	$vitex 	Vitex类的一个实例

#### runNext()
执行下一个预处理中间件调用.对于用户自己定义的中间件需要在call方法中执行 `$this->runNext()` 方法才可以使得继续执行其他的中间件。

**签名**  
`runNext() : void`

**示例**  

`$this->runNext()`

#### call()

此方法是一个抽象方法，所有继承\vitex\middleware的接口都必须要实现此方法。
此方法是Vitex执行调用中间件的时候自动调用的方法，因此必须要实现。

> **注意** call方法中要注意 `$this->runNext()`方法的使用，此方法是执行下一个注册的中间件的触发方法，如果你不是确认要中断中间件的执行请务必要执行此方法。
