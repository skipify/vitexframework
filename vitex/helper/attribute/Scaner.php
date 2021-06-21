<?php


namespace vitex\helper\attribute;


use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\PhpFileCache;
use vitex\Vitex;

/**
 * 扫描所有有效的文件列表
 * 此类为一个单例  方便其他地方添加注解的解析
 * @package vitex\helper\attribute
 */
class Scaner
{
    /**
     * 临时缓存属性
     */
    const CACHE_NAME = "_parse_attributes";

    private static  $_instance;

    private function __construct(){

    }

    /**
     * 获取所有扫描到的文件
     * @return array
     */
    public static function files(){
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        return self::$_instance->getFileInfo();
    }

    /**
     * 获取所有扫描目录的文件信息
     * @return array
     */
    private function getFileInfo()
    {
        if ($data = $this->cache(self::CACHE_NAME)){
            return $data;
        }
        $fileInfos = [];
        $files = $this->getFiles();
        foreach ($files as $file) {
            $fileInfos[] = new FileInfo($file);
        }
        $this->cache(self::CACHE_NAME,$fileInfos);
        return $fileInfos;
    }

    /**
     * 缓存一下查询的内容
     * @param $key
     * @param null $val
     * @return false|mixed
     */
    private function cache($key,$val = null){
        $vitex = Vitex::getInstance();
        /**
         * 调试模式不缓存
         */
        if($vitex->getConfig('debug')){
            return null;
        }
        if(function_exists('apcu_fetch')){
            $cache = new ApcuCache();
        } else {
            $cache = new PhpFileCache(sys_get_temp_dir());
        }
        if($val){
            $cache->save($key,$val,2000);
        } else {
            return $cache->fetch($key);
        }
    }

    /**
     * 扫描获得所有的文件
     * @return array
     */
    private function getFiles()
    {
        /**
         * 扫描的文件列表
         * @var array
         */
        $files = [];
        $vitex = Vitex::getInstance();

        $scanDirs = $vitex->getConfig('attribute_scan_dir');
        foreach ($scanDirs as $scanDir) {
            $files = array_merge($files, $this->scan($scanDir));
        }
        return $files;
    }


    /*
     * 获取一个目录的所有PHP文件
     */
    private function scan($dir)
    {
        $directory = new \RecursiveDirectoryIterator($dir);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regexIterator = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
        $arr = [];
        foreach ($regexIterator as $file){
            $arr[] = $file[0];
        }
        return $arr;
    }
}