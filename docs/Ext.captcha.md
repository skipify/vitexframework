# 验证码使用

验证码类支持两种渲染模式，一种使用传统PHP的GD库渲染，另一种使用ImageMagick来渲染。第一种不需要前置的安装，编译PHP时一般都带有GD扩展，第二种方式效率更高但是需要安装额外的软件：[ImageMagick](http://www.imagemagick.org/script/index.php),然后安装相应的PHP扩展imagick。

此类在使用时如果不指定渲染模式则会自动判断，imagick扩展的优先级大于GD扩展的优先级。

使用方式：

``` php
//初始化一个验证码实例，没有指定参数此时生成一个普通验证码80*40的透明png图片直接header输出到浏览器
$captcha = new \vitex\ext\Captcha();
$captaha->get();
//验证返回的验证码是否正确，正确则返回true否则返回false
\vitex\ext\Catpcha::test("asdf");
```

指定参数

``` php
use \vitex\ext\Captcha;
//此方法生成一个计算型的验证码比如 输入验证码内容为 3+4=?
$catpcha = new Captcha(["type"=>2,"linenum"=>6]);
$captcha->get();

Captcha::test(4);
```



**参数列表**

type  验证码类型 1(default)为普通验证码，2为计算型验证码

length 验证码长度默认为4，此参数仅对type=1时有效，如果长度改变也需要改变相应的宽度

linenum 干扰线的数量默认为4

font 字体文件的绝对路径

width 生成的验证码宽度默认80

height 生成的验证吗高度 默认40，注意生成的验证码字号会根据高度自动计算

## 方法

### get

生成验证码，此方法可以接受一个参数：文件名。如果不指定参数则会默认直接header输出图片到客户端，如果指定文件名则会把图片写入到文件中，文件名一定要是png图片。

### `get(string $file="")`

### test

此方法为一个静态方法，用于验证客户端返回的验证码是否正确。

`Captcha::test(12)`