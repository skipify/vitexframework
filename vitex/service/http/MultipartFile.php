<?php


namespace vitex\service\http;

use vitex\core\Exception;

/**
 * 上传文件的封装
 * 可以实现文件类型判定
 * 文件名生成
 * 写入到其他目录等
 * @package vitex\service\http
 */
class MultipartFile extends \SplFileObject
{
    /**
     * 上传的原始文件名
     * @var string
     */
    private string $orginName;

    /**
     * type类型
     * @var string
     */
    private string $mime;

    /**
     * 文件写入到指定的文件
     * @param $file
     * @throws Exception
     */
    public function writeToFile($file): void
    {
        @mkdir(dirname($file), 0777, true);
        $handle = fopen($file, 'a+');
        while (!$this->eof()) {
            fwrite($handle, $this->fgets());
        }
        fclose($handle);
    }

    /**
     * 写入到指定的目录 会自动生成文件名
     * @param $basePath
     * @return string 返回写入的文件名
     */
    public function writeToPath($basePath): string
    {
        if (!is_dir($basePath)) {
            throw new Exception($basePath . ' must be a directory');
        }
        @mkdir($basePath,0777,true);
        $fileName = $this->generateFileName($basePath);
        $this->writeToFile($fileName);
        return $fileName;
    }

    /**
     * 生成一个新的文件名字
     * @return string
     */
    public function generateFileName($basePath = ''): string
    {
        $filename = uniqid() . '.' . $this->getExtension();
        if ($basePath) {
            return rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        } else {
            return $filename;
        }
    }

    /**
     * 返回用户上传文件的扩展名
     * @return string|void
     */
    public function getExtension()
    {
        $fileName = $this->getOrginName();
        $fs = explode('.', $fileName);
        return end($fs);
    }

    /**
     * 检查当前扩展名是否在允许的列表中
     * 不区分大小写
     * @param array $allowExt
     * @return bool
     */
    public function isAllow($allowExt = []): bool
    {
        $allowExt = array_map(function ($item) {
            return strtoupper($item);
        }, $allowExt);
        return in_array(strtoupper($this->getExtension()), $allowExt);
    }

    /**
     * 获取上传文件的原始文件名
     * @return string
     */
    public function getOrginName(): string
    {
        return $this->orginName;
    }

    /**
     * @param string $orginName
     */
    public function setOrginName(string $orginName): void
    {
        $this->orginName = $orginName;
    }


    /**
     * 获取上传文件的 type  例如 image/png
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * @param string $mime
     */
    public function setMime(string $mime): void
    {
        $this->mime = $mime;
    }


}