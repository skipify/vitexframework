# Acl权限管理

这是一个简易的权限管理工具，通过他可以把相应的权限进行集中管理、验证等；支持单个权限验证、多个权限验证、分组验证、子权限验证等多种类型的权限管理。

## 使用
	
	//现在有以下权限
	


## API

`$acl = new \Vitex\Ext\Acl()`  

###addRule()
添加匹配规则（初始化匹配规则）,添加用于匹配使用的规则，所有的验证匹配都是要验证通过此方法添加规则。  
匹配的规则与添加路由时的规则一致。   

**签名**  
`addRule(\Vitex\Ext\string/array  $pattern)`   
**参数**  
\Vitex\Ext\string/array 	$pattern  规则多个请传数组  
**示例**  
`$acl->addRule('/user/:id')`  
`$acl->addRule(['/user/:name','/login'])`  

###getRule()
获取当前的匹配所使用的权限规则一个数组，返回的数据包括所有分组设定和子权限中的所有权限  
**签名**  
`getRule() : array`  
Returns array —规则列表  
**示例**  
`$rules = $acl->getRule()`  

###clearRule()
清空所有权限规则

###isAllowed()
判断当前指定的URL是否通过权限检测  
**签名**  
`isAllowed(string  $url) : boolean`  
**参数**  
string 	$url 	URL  
**示例**  
`$acl->isAllowed('/user/1')`  

###anyAllowed()
可以传递多个URL的数组，只要有一个规则满足即可返回true  
**签名**  
`anyAllowed($rules, $method='all') : boolean`   

**示例**  
`$acl->anyAllowed(['/user/1','/user/edit'],'all')`  

###addChild()
添加一个子规则对象(子规则也是Acl对象的实例)，把目标对象规则合并到当前对象中。 
**签名**  
`addChild(\Vitex\Ext\Acl  $child) : object`  
**参数**  
\Vitex\Ext\Acl 	$child 	子对象   
**示例**  

	$acl2 = new \Vitex\Ext\Acl();   
	$acl2->addRule('/user/:id');  
	$acl->addChild($acl2);
	$acl->isAllowed('/user/1');//true 

###addGroup()
按照组添加一组整体的权限 注意分组的权限也会被加入到当前对象的权限中去  
**签名**
`addGroup(string  $alias, array  $rules) : object`  
**参数**  
string 	$alias 	规则组名
array 	$rules 	一堆规则一个规则或者一个数组  
**示例**  
	
	$acl->addGroup('user',['user/:id','/auth/login']);   
	$acl->isAllowed('/user/1'); // true;  
	$acl->groupAllowed('user','/auth/login'); // true  

###getGroupRule()
根据分组名获取分组权限  
**签名**  
`getGroupRule(  $alias) : array`  
**参数**  
string $alias 分组名  
**示例**  
`$acl->getGroupRule('user');`  

###getGroup()
获取所有分组信息  
**签名**  
`getGroup() : array`  
**示例**  
	`$acl->getGroup();`  

###groupAllowed() 
 按照分组检查是否有分组权限  
 **签名**  
`groupAllowed(string  $alias,   $pattern) : boolean`  
 **参数**  
string 	$alias 	分组名
string	$pattern 匹配段  
**示例**  

	$acl->addGroup('user',['user/:id','/auth/login']);   
	$acl->isAllowed('/user/1'); // true;  
	$acl->groupAllowed('user','/auth/login'); // true  
	