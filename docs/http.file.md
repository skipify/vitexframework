# 上传文件API

`service\http\File`

该内容继承自 `SplFileObject`

## 生成文件名
生成一个上传使用的文件名
可以指定一个基础路径，最终会拼接位一个绝对地址
`generateFileName($basePath = '')`

## 获取文件原始文件名
获取上传的文件的原始文件名
`getOrginName()`

## 获取上传的原始type
获取上传的原始type 如 image/png
`getMime()`

## 是否允许的扩展名
检查当前文件扩展名是否在给予的允许列中中 不区分大小写
`isAllow(array allow_list)`

## 写入到文件

把当前上传的文件写到指定的目录

`writeToFile($filename)`

## 写入到指定的目录
写入到指定的目录，会自动生成扩展名
`writeToPath($path)`


其他API参考`SplFileObject`