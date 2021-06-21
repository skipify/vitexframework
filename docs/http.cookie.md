# Cookie内容

Cookie封装为一个单独的类 可以使用

```
    $cookie = new Cookie("name","value");
    
    //下述配置有些可以根据配置文件配置
    $cookie->setMaxAge(1000);
    $cookie->setHttpOnly(true);
    $cookie->setSecure(true);
    $cookie->setDomain("xxx.com");
    $cookie->setPath("/");
    $cookie->setSameSite(Cookie::LAX);
    
    $cookie->send();
    //或者
    $request->addCookie($cookie);

```