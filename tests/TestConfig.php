<?php
namespace vitex\tests;

require ('../../vitex/../vendor/autoload.php');


use vitex\middleware\Cookie;
use vitex\Vitex;


class TestConfig extends \PHPUnit\Framework\TestCase
{

    /**
     * 测试配置
     * @throws \vitex\core\Exception
     */
    public function testConfig(){
        $vitex = \vitex\Vitex::getInstance();
        $vitex->setConfig('key',1);
        $this->assertEquals(1,$vitex->getConfig('key'));
    }

    /**
     * 测试视图
     */
    public function testView()
    {
        $vitex = Vitex::getInstance();

        $view = $vitex->view();
        //风格
        $view->setStyle("style");
        $this->assertEquals("style/",$view->style);

        /**
         * 路径
         */
        $view->setTplPath('path');
        $this->assertEquals('path',$view->getTplPath());

        /**
         * string set
         */
        $view->set('data','data-one');
        $this->assertEquals('data-one',$view->get('data'));

        /**
         * arrat set
         */
        $view->set('array_data',['arr'=>'value']);
        $this->assertEquals(['arr'=>'value'],$view->get('array_data'));

        /**
         * object
         */
        $obj = new \stdClass();
        $obj->name = 'vitex';
        $view->set('object_data',$obj);
        $this->assertEquals($obj,$view->get('object_data'));

        /**
         * null
         */
        $view->set('null_data',null);
        $this->assertEquals(null,$view->get('null_data'));

        /**
         * bool
         */
        $view->set('bool_data',false);
        $this->assertEquals(false,$view->get('bool_data'));
    }


    public function testRequest()
    {
        $vitex = Vitex::getInstance();

        $_COOKIE['name'] = 'john';
        $this->assertNull($vitex->req->cookies);


    }
}