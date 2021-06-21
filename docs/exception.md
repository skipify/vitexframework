#异常处理

对于系统的异常如果不想单独处理可以使用统一的处理方式来处理抛出的异常，防止错误发生

具体方式是可以根据不同的异常来注册不同的异常处理逻辑。

如下：
```
$vitex->route->exceptionHandler(Exception::class,function($e){
    echo json_encode([
        'state' => 0,
        'errmsg' => $e->getMessage(),
        'data' => ''
    ]);
});
```


```$vitex->route->exceptionHandler``` 可以接受2个参数，第一个是异常类名，另一个是异常处理的方法。

不建议异常处理有特别复杂的逻辑


此处异常有一个优先级顺序，首先会查看具体的异常是否有独立的处理Handler如果没有会查找 框架自带异常以及PHP根异常，顺序如下：
具体异常->vitex\Exception->\Exception.  如果都找不到则会继续抛出异常。

- 处理异常的具体优先级
- 处理框架自带的Exception
- 处理PHP自带的Exception