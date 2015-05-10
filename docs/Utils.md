#Utils

一些常用的辅助方法，如加密解密

##encrypt() 
加密数据   
**签名**  
`encrypt(string  $data, string  $key, string  $iv = null, array  $setting = array()) : array`  

**参数**  
string 	$data 	要加密的数据
string 	$key 	加密的密钥
string 	$iv 	向量值
array 	$setting 	配置文件，配置加密的模式和加密的方法  

**示例**  


##decrypt()
解密数据

**签名**  
`decrypt(string  $endata, string  $key, string  $iv, array  $setting = array()) : string`  

**参数**  
string 	$endata 	加密的字符串
string 	$key 	密钥
string 	$iv 	向量值
array 	$setting 	配置  
**示例**  