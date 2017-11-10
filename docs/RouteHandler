#路由单独处理

使用路由监控可以单独对于注册的路由进行前后以及包裹性的处理

单个路由可以自持 before after wrap 三个不同类型的处理

三个不同的Handler执行顺序分别为

- before  路由到的方法执行之前
- after   路由到的方法执行之后
- wrap    路由到的方法传递给wrap 可以自定义执行方式

`before` 接收两个参数 一个是 \vitex\core\Request的实例 第二个是 \vitex\core\Response 的实例

`after` 接收两个参数 一个是 \vitex\core\Request的实例 第二个是 \vitex\core\Response 的实例 第三个是 before方法的返回值，如果没有before方法则第三个参数为`null`
`wrap` 接收四个参数 一个是路由到的方法自己控制执行  第二个个是 \vitex\core\Request的实例 第三个是 \vitex\core\Response 的实例 第四个是 wrap方法的返回值，如果没有before方法则第三个参数为`null`

**特别注意**
如果您的路由方法中使用了 `exit`则执行路由方法后的代码不会再执行。除非必要一般在路由方法中使用 `return`来结束路由方法的执行


第一种类型
直接使用匿名函数实现路由的执行调整

```
    //route.php
    <?php

    $vite->get('/','Home@index')->before(function($req,$res){
        echo "route before\n";
    })->after(function(){
        echo "route after\n";
    })->wrap(function($route){

        echo "start wrap\n";
        $route();
        echo "end wrap\n";
    });

```
上述代码输出

route before

start wrap
route ....
end wrap

route after


第二种类型

通过实现\vitex\core\RouteHandlerInterface

```

//Handler.php
namespace site\ext;

use vitex\core\Request;
use vitex\core\Response;
use vitex\core\RouteHandlerInterface;

class Handler implements RouteHandlerInterface
{
    public function before(Request $req,Response $res)
    {
        echo 'before';
        return 'to wrapper';
    }

    public function after(Request $req,Response $res,$data)
    {
        echo $data;//to after
        echo 'after';
    }

    public function wrap(callable $route,Request $req,Response $res,$data)
    {
        echo $data;// to wrapper
        echo '<wrap:';
        $route();
        echo ':wrap>';
        return 'to after';
    }
}

//route.php
<?php

$vite->get('/','Home@index')->handler(new \site\ext\Handler());

```
