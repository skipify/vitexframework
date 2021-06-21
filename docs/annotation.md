# 注解
受惠于PHP8.0的 注解 `Attribute`,系统支持注解功能

注解从不同的维度分类主要分为
从加载时间上分可分为  实时注解和延迟注解
从注解类型上可分为 参数注解、路由注解、系统注解、验证注解、模型注解

## 系统注解

### Autowire 

- 属性注解

自动注入注解，可以实现类的属性自动注入。注意，此类主要注入用户自定义的类型。对于标量类型无法注入。
所有注入的类均使用容器进行单例注入。

```php
class A{
    public function say(){
        echo "hello world";
    }
}

class B{
    
    #[Autowire]
    privatr A $a;
    
    public function doSay(){
        $this->a->say();
    }
}
```

### Caching

- 方法注解

缓存注解 ，此注解需要依赖于 `runkit7` 扩展实现。使用此注解请保证安装有此扩展。

`Caching`注解主要用于耗时长的方法的使用。通过给方法包装一层处理逻辑在一次请求中对于相同参数的调用返回缓存值


```php
class A{
    
    #[Caching]
    public function getSleep(){
        sleep(1);
        echo "time--" . time();
    }
}
```

### HookListener
- 方法注解

钩子注解，此方法用于钩子监听器使用,主要用于控制器.
次注解可以接受2个参数，
- 第一个参数为钩子名称
- 第二个为可选的参数用于指定是否为只执行一次  HookListener::TYPE_ONCE

```php
    class A extends Controller{
        public function say(){
            $this->emit("this is a hook");
        }
        
        @HookListener("this is a hook")
        public function dowithSay(){
            echo 'I has Run';
        }
    }

```

## 路由注解

### Route

- 类注解
- 方法注解

此注解可以用于方法或者类，用于类时会忽略请求方法，所设置路径为基路径，旗下所有方法的路径都会拼接此路径。

### Get Put Delete Post 



```php
#[Route("/my")]
class Test
{


    #[Route("/a","GET")]
    public function show(){

    }
    #[Route("/save","POST")]
    public function save(){

    }

    #[Post("/save1")]
    public function save1(){

    }

    #[Delete("/save1")]
    public function save2(){

    }
}
```

## 参数注解

### RequestBody

- 参数注解

主要用于控制器，用于自动注入用户提交的数据

使用此方法需要2步操作

第一步：设置一个实体类用于存储用户数据,属性需要使用`public`修饰(配合验证注解更强大)
第二步：在控制器方法中使用。可以按照 `php://input`  `$_POST` `$_GET`的顺序加载数据，并使用 Safe安全策略过滤。
```php
class UserInfo{
    public stirng $name;
}

public User extends Controller{
    
    public function saveUser(#[RequestBody] UserInfo $userInfo){
        echo $userInfo->name;
    }
}
```

### RequestParam

- 参数注解

主要用于控制器，用于自动注入用户提交的单个数据


可以按照 `php://input`  `$_POST` `$_GET`的顺序加载数据，并使用 Safe安全策略过滤。
```php

// ?name=john
public User extends Controller{
    
    public function saveUser(#[RequestParam] string $name){
        echo $userInfo->name;
    }
}
```

如果参数名与前台提交字段名不一致可以指定参数来修正


```php

// ?name=john
public User extends Controller{
    
    public function saveUser(#[RequestParam("name")] string $userName){
        echo $userInfo->name;
    }
}
```

上传文件注解
文件注解的类型必须为 `use vitex\service\http\MultipartFile`

```php
public User extends Controller{
    
    public function saveUser(#[RequestParam("f")] MultipartFile $file){
        $file->writeToFile(WEBROOT . "/a.zip");
    }
}

```

## 验证注解

- 属性注解

验证类的注解主要用于接收来自用户提交的数据，需要一个实体类来承载数据

一个简单的例子
```php
#[Validate]
class DataInfo
{
    /**
     * 错误信息
     * @var string
     */
    public string $_errorTipStr;
    public array $_errorTip;



    #[Tstring(min:5,max: 20,fieldName: '用户名')]
    public string $name = '';


    #[Required(fieldName: '年龄')]
    #[Tint(min:0,max: 10,fieldName: "年龄")]
    public int $age = 0;
}

```

**注意**
所有注解都存在 errMsg fieldName 参数

注解最少包含2个参数
- errMsg  错误信息
- fieldName 字段名字

如果提供了 errMsg 则如出现错误则使用errMsg提示，

如果没有提供errMsg
- 提供了 fieldName参数则会使用默认注解添加 fieldName的形式 例如 姓名为必填项
- 没有提供 fieldName 则会使用字段的名称提示  例如 name为必填项

参数可以使用`PHP8`的命名参数来简化操作

```php
#[Required(fieldName:'姓名')]
```


**强烈注意，需要验证字段类型的实体类必须要使用 `#[Validate]` 注解**


### Required

必填注解，此注解标记的字段需要包含值，  '',[],null 会被当做空，其他值不当做空

默认提示语

`{attr}为必填项`
示例
```php
#[Required]
public string $name;
```


### Enum

枚举注解

参数
- $enums  指定符合条件的枚举值，为一个数组

默认提示语
`{attr}的值{val}不在允许的列表`

示例

```php
#[Enum([1,2])]
public int $sex;
```

### Regexp
正则注解

参数
- $pattern  一个正则吧表达式

默认提示语

`{attr}不符合规则`

示例

```php
#[Regexp("^[0-9]+$")]
public string $number;
```

### Tstring

字符串类型

参数

- $min
- $max

默认提示语

> {attr}必须为一个字符串
> {attr}长度为{min}和{max}之间
> {attr}长度需大于{min}个字符
> {attr}长度需小于{max}个字符

### Tint

数字类型

- $min
- $max

> {attr}必须为一个整数
> {attr}必须为{min}和{max}之间的整数
> {attr}必须为大于{min}的整数
> {attr}必须为小于{max}的整数


### Tfloat

浮点型

参数

- $min
- $max

默认提示语


> {attr}必须为一个浮点数
> {attr}必须为{min}和{max}之间的浮点数
> {attr}必须为大于{min}的浮点数
> {attr}必须为小于{max}的浮点数

### IsEmail

- 是否是合法邮箱格式

默认提示语

```
{attr}必须为一个合法邮箱地址
```

### IsIdCard

是否合法身份证号

```
{attr}不合法
```

### isUrl

是否合法URL链接

```
{attr}必须为一个合法的URL
```

### isMobile

是否合法的手机号

```
{attr}不是合法手机号码
```