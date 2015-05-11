<?php
/**
 * Vitex 一个基于php5.5开发的 快速开发restful API的微型框架
 * @version  0.3.0
 *
 * @package Vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

class Init
{
    public function __construct($dirname = '.', $appname = 'app')
    {
        $dirname       = rtrim($dirname, '/');
        $this->appname = $appname;
        $this->dirname = $dirname;
        $this->dirs    = [
            $dirname . '/' . $appname . '/Route',
            $dirname . '/' . $appname . '/Model',
            $dirname . '/' . $appname . '/Ext',
            $dirname . '/' . $appname . '/Templates',
            $dirname . '/' . $appname . '/Controller',
            $dirname . '/webroot/public',
        ];

    }

    public static function init()
    {
        $dirname = getcwd();
        echo '
            *****************************************

                          欢迎使用初始化系统
            使用本系统之前你应该先阅读vitex部署相关的内容

            *****************************************
        ';
        echo '请输入生成代码的路径，即webroot文件夹所在路径(' . $dirname . '):';
        $_dirname = fread(STDIN, 200);
        if (trim($_dirname)) {
            $dirname = $_dirname;
        }
        echo $dirname . PHP_EOL;

        echo '请输入应用名字，此名字应该是你的应用名且为命名空间的名字(app)：';
        $appname  = 'app';
        $_appname = fread(STDIN, 200);
        if (trim($_appname)) {
            $appname = $_appname;
        }
        echo $appname . PHP_EOL;

        echo '按回车确认';
        fread(STDIN, 1);

        $init = new Init($dirname, $appname);
        $init->create();
    }

    public function create()
    {
        $index = $this->index();
        if (!$index) {
            echo '您的目录已经存在项目，请确认!!!';
            exit;
        }
        mkdir($this->dirname . '/' . $this->appname);

        foreach ($this->dirs as $dir) {
            @mkdir($dir);
        }

        $this->route();
        $this->model();
        $this->tpl();
        $this->controller();
    }

    /**
     * 生成首页入口文件
     * @return string
     */
    private function index()
    {
        $indexfile = $this->dirname . '/webroot/index.php';
        @mkdir($this->dirname . '/webroot/');

        if (file_exists($indexfile)) {
            return false;
        }
        $code = 'PD9waHAKcmVxdWlyZSAnLi4vdmVuZG9yL2F1dG9sb2FkLnBocCc7CiR2aXRleCA9IFxWaXRleFxWaXRleDo6Z2V0SW5zdGFuY2UoKTsKCmNvbnN0IFdFQlJPT1QgPSBfX0RJUl9fOwovL+mFjee9rui3r+eUseaWh+S7tuWcsOWdgAovL+mFjee9ruaooeadv+i3r+W+hAovL+mFjee9ruWIm+W7uueahOW6lOeUqGFwcO+8jOaKiuivpeebruW9leWinuWKoOWIsOiHquWKqOWKoOi9veeahOWQjeWNleS4rQokdml0ZXgtPmluaXQoJ3thcHB9JywgZGlybmFtZShfX0RJUl9fKSk7Cgokdml0ZXgtPnVzaW5nKG5ldyBcVml0ZXhcTWlkZGxld2FyZVxTZXNzaW9uKCkpOwoKJHZpdGV4LT5hbGwoJy8nLCBmdW5jdGlvbiAoKSB7CiAgICBlY2hvICc8aDE+V2VsY29tZSBWaXRleCEhPC9oMT4nOwp9KTsKCiR2aXRleC0+Z3JvdXAoJy93ZWxjb21lJywgJ0luZGV4Jyk7CiR2aXRleC0+Z2V0KCcvdXNlcicsJ1VzZXInKTsgLy/osIPnlKhDb250cm9sbGVy5LitVXNlcuexu+eahGdldOaWueazlQoKJHZpdGV4LT5ydW4oKTs=';
        $code = base64_decode($code);
        //创建新文件
        file_put_contents($indexfile, str_replace('{app}', $this->appname, $code));

        return true;
    }

    //模型示例
    public function model()
    {
        $file = $this->dirname . '/app/Model/Index.php';
        $code = 'PD9waHAKLyoK6L+Z5piv5LiA5Liq5pmu6YCa5qih5Z6LCiAqLwpuYW1lc3BhY2UgQXBwXE1vZGVsOwoKY2xhc3MgSW5kZXggZXh0ZW5kcyBcVml0ZXhcRXh0XE1vZGVsCnsKICAgIHB1YmxpYyBmdW5jdGlvbiBfX2NvbnN0cnVjdCgpCiAgICB7CiAgICAgICAgcGFyZW50OjpfX2NvbnN0cnVjdCgpOwogICAgICAgIC8v6buY6K6k55qE6KGo5ZCN5piv57G75ZCNIOacrOS+i+S4uiBpbmRleCjlsI/lhpkpCiAgICAgICAgLy/pu5jorqTnmoTkuLvplK7kuLogaWQKICAgICAgICAvL+WPr+S7peWcqOi/memHjOmHjeaWsOiuvue9ruS4u+mUruWSjOihqOWQjQogICAgICAgICR0aGlzLT5wayAgICA9ICdpZCc7CiAgICAgICAgJHRoaXMtPnRhYmxlID0gJ3VzZXInOwogICAgfQp9Cg==';
        file_put_contents($file, base64_decode($code));
        return true;
    }

    //路由
    public function route()
    {
        $file = $this->dirname . '/app/Route/Index.php';
        $code = 'PD9waHAKCiR2aXRleC0+Z2V0KCcvanNvbicsIGZ1bmN0aW9uICgkcmVxLCAkcmVzKSB7CiAgICAgICAgICAkcmVzLT5qc29uKFsnbmFtZScgPT4gJ3ZpdGV4J10pOwogICAgICB9KQogICAgICAtPmdldCgnLycsIGZ1bmN0aW9uICgkcmVxLCAkcmVzKSB7CiAgICAgICAgICAkcmVzLT5yZW5kZXIoJ3dlbGNvbWUnKTsKICAgICAgfSk7Cg==';
        file_put_contents($file, base64_decode($code));
        return true;
    }

    public function tpl()
    {
        $file = $this->dirname . '/app/Templates/welcome.html';
        $code = 'PGh0bWw+CjxoZWFkPgoJPHRpdGxlPldlbGNvbWU8L3RpdGxlPgo8L2hlYWQ+Cjxib2R5Pgo8aDE+V2VsY29tZTwvaDE+CjwvYm9keT4KPC9odG1sPg==';
        file_put_contents($file, base64_decode($code));
        return true;
    }

    public function controller()
    {
        $file = $this->dirname . '/app/Controller/Controller.php';
        $code = 'PD9waHAKbmFtZXNwYWNlIEFwcFxDb250cm9sbGVyOwoKdXNlIFxWaXRleFxDb250cm9sbGVyIGFzIFZjb250cm9sbGVyOwoKY2xhc3MgQ29udHJvbGxlciBleHRlbmRzIFZjb250cm9sbGVyCnsKCn0K';
        file_put_contents($file, base64_decode($code));
        $file = $this->dirname . '/app/Controller/User.php';
        $code = 'PD9waHAKbmFtZXNwYWNlIEFwcFxDb250cm9sbGVyOwoKY2xhc3MgVXNlciBleHRlbmRzIENvbnRyb2xsZXIKewogICAgcHVibGljIGZ1bmN0aW9uIGdldCgpCiAgICB7CiAgICAgICAgZWNobyAndXNlcic7CiAgICB9Cn0K';
        file_put_contents($file, base64_decode($code));
    }

}

Init::init();
