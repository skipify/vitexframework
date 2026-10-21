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
        //路径穿越防护：拒绝携带 .. 的写入路径
        if (str_contains((string)$file, '..')) {
            throw new Exception('非法的写入路径:' . $file, Exception::CODE_PARAM_VALUE_ERROR);
        }
        @mkdir(dirname($file), 0777, true);
        $handle = fopen($file, 'a+');
        while (!$this->eof()) {
            fwrite($handle, $this->fgets());
        }
        fclose($handle);
    }

    /**
     * 写入到指定的目录 会自动生成文件名
     * 必须传入允许的扩展名白名单，未通过白名单校验的文件将被拒绝写入
     * @param $basePath
     * @param array $allowExt 允许的扩展名白名单 如 ['jpg','png','gif']
     * @return string 返回写入的文件名
     * @throws Exception
     */
    public function writeToPath($basePath, array $allowExt = []): string
    {
        if (!$allowExt) {
            throw new Exception('必须指定允许的扩展名白名单，如 ["jpg","png"]', Exception::CODE_PARAM_VALUE_ERROR);
        }
        if (!$this->isAllow($allowExt)) {
            throw new Exception('文件扩展名不在允许的白名单之内:' . $this->getExtension(), Exception::CODE_PARAM_VALUE_ERROR);
        }
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
        $filename = bin2hex(random_bytes(16)) . '.' . $this->getExtension();
        if ($basePath) {
            return rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        } else {
            return $filename;
        }
    }

    /**
     * 返回用户上传文件的扩展名
     * 强校验：仅允许字母数字组合(1-20位)，防止通过原始文件名携带 / ../ 等字符造成路径穿越
     * @return string
     * @throws Exception
     */
    public function getExtension(): string
    {
        $fileName = $this->getOrginName();
        if (!str_contains($fileName, '.')) {
            throw new Exception('上传文件缺少扩展名:' . $fileName, Exception::CODE_PARAM_VALUE_ERROR);
        }
        $fs = explode('.', $fileName);
        $ext = (string)end($fs);
        if (!preg_match('/^[A-Za-z0-9]{1,20}$/', $ext)) {
            throw new Exception('上传文件的扩展名不合法:' . $ext, Exception::CODE_PARAM_VALUE_ERROR);
        }
        return $ext;
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