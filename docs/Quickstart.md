# 开始

开始之前请先确定您已经安装了 `php5.5+` ，接下来就可以安装使用Vitex了。

## 安装

	使用 `php composer install`安装  

	

	

	<?php

	require '../Vitex/Vitex.php';

	$vitex = \Vitex\Vitex::getInstance();

	$vitex->all(‘/user', function ($req,$res,$next) {

		echo "Hello Vitex!!";

	});

	$vitex->run();

	

解释一下上面的程序：

当访问 `/`时会自动调用匿名函数处理 输出 Hello Vitex!!

## 使用模板

	$vitex->get('/user', function ($req,$res) {

		$res->render("user",["name"=>"Vitex"]);

	});

	或  

	$vitex->get('/user', function ($req,$res) use ($vitex){

		$vitex->render("user",["name"=>"Vitex"]);

	});

	Vitex 和 Response类都有相同的render方法

## 自动初始化一个项目

Vitex定位于中小型项目，主要是API开发。

初始化项目请使用 `Vitex/Ext/Init.php` 来初始化

直接在项目根目录中使用终端(*nix),或者命令行直接调用：

	`php Vitex/Ext/Init.php`  

注意你的 Init.php的目录位置

如网站目录为  `/home/www/website`  那么 你使用 vitex初始化工具时填写的路径应该是  `/home/www/website`  那么你的网站根目录(域名绑定路径)为  `/home/www/website/webroot/`

即可以初始化一个项目

如果你使用composer管理，目录结构可能是这样的



  ——webroot

  ————public

  ————index.php

  ——app  

``` 
————routes  
————models  
```

	————templates

	————exts  

  ——vendor  

  ——composer.json  

** webroot为HTTP访问的根目录，域名应该绑定在该目录 **  

也就是vitex推荐的目录布局是在网站访问目录仅仅包含 index.php入口文件（可以多个）

如网站目录为  `/home/www/website`  那么 你使用 vitex初始化工具时填写的路径应该是  `/home/www/website`  那么你的网站根目录为  `/home/www/website/webroot/`