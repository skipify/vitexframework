<?php


namespace vitex\tests;

require ('../../vendor/autoload.php');


use PHPUnit\Framework\TestCase;
use vitex\service\model\sql\SelectWrapper;


/**
 * 测试拼接sql字符串
 * @package vitex\tests
 */
class TestWrapper extends TestCase
{

    public function testCondation()
    {
        $selectWrapper = new SelectWrapper();
        echo $selectWrapper->from('user u')->eq("id",1);
    }
}