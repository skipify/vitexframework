<?php declare(strict_types=1);
/**
 * Vitex 一个基于php8.0开发的 快速开发restful API的微型框架
 * 再下一个版本会删除
 * @version  2.0.0
 *
 * @package vitex
 * @deprecated  请直接使用request中的ufile
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 * 请使用 File替代  $this->>req->ufile;
 *
 */
namespace vitex\ext;

use vitex\middleware;
use vitex\Vitex;

/**
 * 文件上传的中间件，既可以当做中间件使用有可以当做单独的应用程序
 */
class Upload extends Middleware
{
    /**
     * 默认的配置文件
     * ext默认为常见资源类型扩展名白名单(多个用|分割)，'*'为显式放行任意扩展名(不推荐)
     * @var array
     */
    public $setting = [
        'ext'       => 'jpg|jpeg|png|gif|webp|bmp|ico|svg|avif|pdf|txt|csv|md|doc|docx|xls|xlsx|ppt|pptx|zip|rar|7z|gz|tar|mp3|wav|flac|mp4|avi|mov|mkv|webm',
        'fieldname' => '',
        'rename'    => null,
        'dest'      => '',
    ];

    /**
     * 可执行/服务端解析类危险扩展名，无论白名单或通配如何配置一律硬拒绝
     * 防止上传php/phtml/htaccess等导致RCE
     */
    private const DANGER_EXTS = [
        'php', 'phtml', 'pht', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phps', 'phtm',
        'htaccess', 'htpasswd', 'user.ini', 'ini',
        'jsp', 'jspx', 'jspa', 'asp', 'aspx', 'ascx', 'asax', 'ashx', 'asmx', 'cer', 'cdx',
        'exe', 'dll', 'so', 'bat', 'cmd', 'com', 'sh', 'bash', 'cgi', 'pl', 'py', 'rb',
    ];
    /**
     * 错误信息列表
     * @var array
     */
    private $errorInfo = [];
    private $return    = [];
    /*
    dest //目标
    rename
    fieldname
    ext
     */
    public function __construct($setting = [])
    {
        $this->setting = array_merge($this->setting, $setting);
    }

    /**
     * 用于对上传的文件重命名
     * @param  string $fieldName        前台传递的表单名
     * @param  string $filename         前台传递的文件原始名字
     * @param  string $ext              扩展名
     * @return string 新的文件名
     */
    public function rename($fieldName, $filename, $ext)
    {
        $filename = md5(VITEX_NOW . $fieldName . $filename . rand(1, 999)) . '.' . $ext;
        return $filename;
    }

    /**
     * 设置存储路径
     * @param  string $path 路径，绝对路径
     * @return self
     */
    public function setDest($path)
    {
        $this->setting['dest'] = $path;
        return $this;
    }

    /**
     * 获取当前的文件上传列表
     * @return array
     */
    private function getFiles()
    {
        $fieldname = $this->setting['fieldname'];
        if ($fieldname) {
            $_files = isset($_FILES[$fieldname]) ? $_FILES[$fieldname] : array();
            $files  = [$fieldname => $_files];
        } else {
            //all
            $files = $_FILES;
        }
        return $files;
    }

    /**
     * 根据扩展名过滤上传的文件,该方法会生成一个错误列表
     * 即使配置为'*'通配，可执行类危险扩展名也会被硬拒绝
     * @return array 过滤后的文件
     */
    private function filterFile($files)
    {
        if (strpos($this->setting['ext'], '*') !== false) {
            return $this->denyDangerousExt($files);
        }
        $exts = explode('|', $this->setting['ext']);
        $exts = array_map(function ($ext) {
            return strtolower(trim($ext));
        }, $exts);

        foreach ($files as $field => &$file) {
            if (!isset($file['name'])) {
                continue;
            }
            $name = $file['name'];
            if (is_array($name)) {
                foreach ($name as $k => $v) {
                    $ext = $this->getExt($v);
                    if ($this->isDangerExt($ext)) {
                        unset($file['name'][$k]);
                        unset($file['type'][$k]);
                        unset($file['tmp_name'][$k]);
                        unset($file['error'][$k]);
                        unset($file['size'][$k]);
                        $this->setError('500', $field . '字段中的' . $v . '文件为不允许上传的危险类型');
                    } elseif (!in_array($ext, $exts, true)) {
                        unset($file['name'][$k]);
                        unset($file['type'][$k]);
                        unset($file['tmp_name'][$k]);
                        unset($file['error'][$k]);
                        unset($file['size'][$k]);
                        $this->setError('500', $field . '字段中的' . $v . '文件不在允许的扩展名之内');
                    }
                }
            } else {
                $ext = $this->getExt($name);
                if ($this->isDangerExt($ext)) {
                    unset($files[$field]);
                    $this->setError('500', $field . '字段中的' . $name . '文件为不允许上传的危险类型');
                } elseif (!in_array($ext, $exts, true)) {
                    unset($files[$field]);
                    $this->setError('500', $field . '字段中的' . $name . '文件不在允许的扩展名之内');
                }
            }
        }
        return $files;
    }

    /**
     * 通配模式下仅硬拒绝危险扩展名
     * @param array $files
     * @return array
     */
    private function denyDangerousExt($files)
    {
        foreach ($files as $field => &$file) {
            if (!isset($file['name'])) {
                continue;
            }
            $name = $file['name'];
            if (is_array($name)) {
                foreach ($name as $k => $v) {
                    if ($this->isDangerExt($this->getExt($v))) {
                        unset($file['name'][$k]);
                        unset($file['type'][$k]);
                        unset($file['tmp_name'][$k]);
                        unset($file['error'][$k]);
                        unset($file['size'][$k]);
                        $this->setError('500', $field . '字段中的' . $v . '文件为不允许上传的危险类型');
                    }
                }
            } else {
                if ($this->isDangerExt($this->getExt($name))) {
                    unset($files[$field]);
                    $this->setError('500', $field . '字段中的' . $name . '文件为不允许上传的危险类型');
                }
            }
        }
        return $files;
    }

    /**
     * 判断扩展名是否为危险的可执行/服务端解析类型
     * @param  string $ext 已小写的扩展名
     * @return bool
     */
    private function isDangerExt(string $ext): bool
    {
        return in_array($ext, self::DANGER_EXTS, true);
    }
    /**
     * 根据文件名获取扩展名
     * @param  string $filename   文件名
     * @return string 扩展名
     */
    private function getExt($filename)
    {
        $fs = explode('.', $filename);
        return strtolower(array_pop($fs));
    }

    //转移上传的文件
    /**
     * 转移上传的文件
     */
    private function moveFile()
    {
        $files = $this->filterFile($this->getFiles());
        if (count($files) == 0) {
            return false;
        }
        $ret = [];
        //转移文件
        foreach ($files as $field => $file) {
            if (!isset($file['name'])) {
                continue;
            }
            $name = $file['name'];
            if (is_array($name)) {
                foreach ($name as $k => $v) {
                    if ($file['error'][$k] != 0) {
                        $this->setError($file['error'][$k], '上传文件发生错误');
                        continue;
                    }
                    $newname = $this->setting['rename'] ? $this->setting['rename']($field, $v, $this->getExt($v)) : $this->rename($field, $v, $this->getExt($v));
                    $path    = rtrim($this->setting['dest'], '/') . '/' . $newname;
                    $ismove  = move_uploaded_file($file['tmp_name'][$k], $path);
                    if (!$ismove) {
                        $this->setError('501', $v . '文件无法转移到指定的目录' . $path);
                    } else {
                        $ret[] = [
                            'filedname'    => $field,
                            'originalname' => $v,
                            'name'         => $newname,
                            'mimetype'     => $file['type'][$k],
                            'path'         => $path,
                            'ext'          => $this->getExt($v),
                            'size'         => $file['size'][$k],
                        ];
                    }
                }
            } else {
                if ($file['error'] != 0) {
                    $this->setError($file['error'], '上传文件发生错误');
                    continue;
                }
                $newname = $this->setting['rename'] ? $this->setting['rename']($field, $name, $this->getExt($name)) : $this->rename($field, $name, $this->getExt($name));
                //转移文件
                $path   = rtrim($this->setting['dest'], '/') . '/' . $newname;
                $ismove = move_uploaded_file($file['tmp_name'], $path);
                if (!$ismove) {
                    $this->setError('501', $name . '文件无法转移到指定的目录' . $path);
                } else {
                    $ret[] = [
                        'filedname'    => $field,
                        'originalname' => $name,
                        'name'         => $newname,
                        'mimetype'     => $file['type'],
                        'path'         => $path,
                        'ext'          => $this->getExt($name),
                        'size'         => $file['size'],
                    ];
                }
            }
        }
        $this->return = $ret;
        return $this;
    }

    /**
     * 执行中间件和上传操作的方法
     */
    public function call()
    {
        if ($this->vitex) {
            $vitex = $this->vitex;
        } else {
            $vitex = Vitex::getInstance();
        }
        $this->moveFile();
        $vitex->req->upload      = $this->return;
        $vitex->req->uploadError = $this->getError();
        $this->runNext();
        return $this->return;
    }

    /**
     * 设置错误信息
     * @param string $code 错误代码
     * @param String $msg  错误信息
     */
    private function setError($code, $msg)
    {
        $this->errorInfo[] = array(
            'code' => $code,
            'msg'  => $msg,
        );
    }
    /**
     * 获取错误信息
     * @return array 错误信息
     */
    public function getError()
    {
        return $this->errorInfo;
    }
}
