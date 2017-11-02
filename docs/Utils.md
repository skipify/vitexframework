#Utils

一些常用的辅助方法，如加密解密,0.10.0 开始 改为openssl实现方式加密

##encrypt() 
加密数据   
**签名**  
`encrypt(string  $data, string  $key, array  $setting = array()) : array`  

**参数**  
string 	$data 	要加密的数据
string 	$key 	加密的密钥
array 	$setting 	配置文件，配置加密的模式和加密的方法  

**示例**  


##decrypt()
解密数据

**签名**  
`decrypt(string  $endata, string  $key, array  $setting = array()) : string`  

**参数**  
string 	$endata 	加密的字符串
string 	$key 	密钥
array 	$setting 	配置  
**示例**  

## phpVersion()

获取当前运行PHP版本是否大于某一个指定的PHP版本

**签名**

`phpVersion(string $version)`

**参数**  
string 	$version 例如 5.6 