# Csrf 跨站防御

系统包含一个中间件 Csrf,此中间件具有完整的防御csrf功能。

三个配置项 可以设置在配置文件 或者单独设置
```
    'csrf.open' => true,
    'csrf.onmismatch' => null,//一个回调方法 callable，当token不匹配出错的时候回执行
    'csrf.except'  => [], //排除的路由规则
```

## 简介

跨站请求伪造是一种通过伪装授权用户的请求来利用授信网站的恶意漏洞。Csrf中间件使得防止应用遭到跨站请求伪造攻击变得简单。

中间件自动为每一个被应用管理的有效用户会话生成一个 CSRF_token “令牌”，该令牌用于验证授权用户和发起请求者是否是同一个人。

任何时候在框架管理的应用中定义HTML表单，都需要在表单中引入CSRF令牌字段，这样CSRF保护中间件才能够正常验证请求。
想要生成包含 CSRF 令牌的隐藏输入字段，可以使用变量 csrf_token_html 来实现：

```
<form method="POST" action="/test">
    <?=$csrf_token_html?>
    ...
</form>
```

中间件中的`verify`方法会自动验证所有  `POST` `PUT`发起的请求。


## 使用

框架本身提供三个配置项

```
'csrf.open' => true, //打开csrf防御
'csrf.onmismatch' => null,//一个回调方法 callable，当token不匹配出错的时候回执行
'csrf.except'  => [], //排除的路由规则列表，在此列表的请求不会验证csrf_token
```

## Token

中间件执行时会自动生成两个变量 ，方便在模板中使用

`csrf_token` `csrf_token_html`

两个变量可以直接在模板中使用

```
<?=$csrf_token_html?>
<?=$csrf_token?>
```

同时 系统会自动设置一个名为 `csrf_token` 的cookie选项，以及一个 `X-Csrf-Token`的 http请求头的数据，均为生成的token值。



如果需要在逻辑程序中使用该变量可以使用 Response的实例获取

```
    $res->get("csrf_token");
    $res->get("csrf_token_html");
```

## 异步请求的使用


除了将 CSRF 令牌作为 POST 参数进行验证外，还可以通过设置 X-CSRF-Token 请求头来实现验证，中间件会检查 X-CSRF-TOKEN 请求头，首先创建一个 meta 标签并将令牌保存到该 meta 标签：

```
<meta name="csrf-token" content="<?=$csrf_token?>">
```

然后在 js 库（如 jQuery）中添加该令牌到所有请求头，这为基于 AJAX 的应用提供了简单、方便的方式来避免 CSRF 攻击：

这样可以批量设置
```
$.ajaxSetup({
    headers: {
        'X-Csrf-Token': $('meta[name="csrf-token"]').attr('content')
    }
});

```


单页页面设置
```
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': '<?=$csrf_token?>'
    }
});
```

当然也可以用笨办法,每个请求单独设置

```
    $.post("/test",{
        _token:'<?=$csrf_token?>'
    },function(){
    
    })
```

## 不匹配token时的处理

1. 默认会抛出一个 `TokenMismatchException` 异常，可以捕获这个异常处理

2. 可以指定一个 `csrf.onmismatch` 的配置 来指定 一个回调函数 函数接收两个参数 第一个为系统默认的token 值 另一个是前台提交的token值

## 排除需要验证token的链接

在 `csrf.except` 中设置要排除的链接

```
'csrf.except' => ['/auth']
```