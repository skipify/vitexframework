# Model

## 前置条件

使用此类之前必须要加载 PDO类库，设置好数据库链接等。 

## 链接配置
链接数据库配置比较简单
系统可以提供一个 主从数据库配置，对于读取相关的操作从从数据库读取，写操作从主数据库读取

如果不使用主从数据库可以使用 `默认数据库`配置,默认数据库使用 `db`键值

支持只设置 `master`配置

```
    'database' => [
        'master' => [
            'host' => '127.0.0.1',
            'database' => 'test',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8'
        ],
        'slaver' => [
            'host' => '127.0.0.1',
            'database' => 'test',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8'
        ],
        'db' => [
            'host' => '127.0.0.1',
            'database' => 'test',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8'
        ]
    ],
```

```
class MyModel extends Model
{
    public function __construct()
    {
        parent::__construct();
    }
}

```
// 如果是要更换数据库链接请使用changeDatabase()方法
```
class MyModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->changeDatabase(/*other setting*/);
    }
}
```


``` 
$vitex->using(new \vitex\ext\Pdo([
	'host'     => 'localhost',
	'database' => 'test',
	'charset'  => 'utf8',
], 'root', 'root'));  
```

第一个参数 可以是一个数组，包含了 host、database、charset信息，也可以是一个 PDO DSN的链接字符串，还可以是一个PDO的链接对象（此时无需指定后2个参数）。

第二个参数为用户名   

第三个参数为密码   

`$vitex->using(new \vitex\ext\Pdo(’mysql:dbname=test;host=localhost;charset=utf8‘, 'root', 'root'));`

Model是一个简单的ORM，非常轻量级的数据库操作类。

## 使用

``` 
use \vitex\ext\Model;

class User extends Model

{

}
```

user 表存在三个字段  id  name age;   

如上声明了一个继承自Model的user类，默认情况下会按把类名（小写）当做表名来查询数据，默认的主键为`id`，如上例子：

``` 
$user = new User();  
//简单查询   
$user->get(1); // select * from user where id = 1  
//  根据上一个查询结果直接修改值  	
$user->name = "Vitex";
$user->save(); // update user set name = 'Vitex' where id = 1;  // 会自动调用 get方法设置的主键ID进行修改
// 按照条件查找指定字段内容  
$user->select('id')->where('age','>',18)->getAll(); // select id from user where age > 18  
//子查询  
$user->whereIn('age',Model::sub()->from('user')->select(age)->where('id', '>', 10))->getAll();  
select * from user where age in (select age from user where id > 10)  
```

[更多示例](Ext.Model.Example.html)    

## API

### init
初始化数据库,对于未默认进行数据库链接的程序可以使用此方式在model类的子类中单独链接程序,已经连接过数据库时此方法不会做任何事情.

如果需要重新连接其他的数据库,请使用changeDatabase方法

```
$this->init([
            'username' => 'root',
            'password'=>'root'
            'host'     => 'localhost',
            'database' => 'test',
            'charset'  => 'utf8',
        ]);
```

### changeDatabase
切换数据库链接，用于程序中动态改变链接其他的数据库，应用的范围是该模型层


**签名**

``` 
changeDatabase(array|string $setting):object
```

**参数**

- array $setting 数据库配置，参考 PDO类
- string 可以为配置文件 database键值下面的一个配置比如 `master` `slaver`

``` 
    "host"     => '数据库服务器',
    "port"     => "3306",
    'database' => '数据库名',
    "username" => "用户名",
    "password" => "密码",
    "charset"  => 'utf8',
```

**示例**

``` 
$this->changeDatabase([
    "host"     => '数据库服务器',
    "port"     => "3306",
    'database' => '数据库名',
    "username" => "用户名",
    "password" => "密码",
    "charset"  => 'utf8',
])
```

如果您执行了 changeDatabase 方法后又想要恢复原来的数据库链接,则可以使用:

```
$this->changeDatabase("master");
```

### def()

定义一个新的模型数据，也就是说初始化一条记录，这条记录的字段应该是与数据库对应的,支持链式操作        

**签名**  

`def(array  $arr = array()) `  

**参数**  

array 	$arr 	数据数组，字段对应数据库中表的字段  

**示例**  

`$model->def(['name'=>'Vitex','age'=>26])`

### setPrefix()

设置表前缀,此方法直接返回对象本身支持链式操作,支持链式操作      

**签名**  

`setPrefix(string  $prefix) : object`  

**参数**  

string 	$prefix 前缀

**示例**  

`$model->setPrefix('cms_')`  


### sub()

返回当前对象的实例，此方法为一个静态方法会返回新实例化的Model类，一般用于子查询实例化model,支持链式操作    

**签名**  

`sub() : object`  

**示例**  

`\vitex\ext\Model::sub()->from('user')->select('id')`  

//如果当做条件传递给`where`会自动调用toString方法转为字符串 `select id from user `  

### query()

直接执行sql语句 @#_ 当做表前缀替换掉    

**签名**  

`query(string  $sql) : mixed`  

**参数**  

string 	$sql 	sql语句

**示例**  

`$model->query("select * from @#_user") ` // select * from cms_user  

### select()

设置要查询的字段名,支持链式操作    

**签名**  

`select(mixed  $column = '*') : object`  

**参数**  

mixed 	$column 可以是字符串，多个字段用,分开，也可以是数组每个元素为一个字段，也可以是*  

**示例**  

``` 
$model->select("*")  
$this->select('id,name')  
$this->select(['name','id'])  
$this->select("user.name as uname")
```

### whereRaw()

字符串形式的查询语句,使用该方法一定要了解你在做什么，此方法设置的条件总是会在where设置的条件之后，

也就是说如果你使用where设置了条件那么你使用本方法设置时应该注意前面添加  and/or 等连接符,支持链式操作          

**签名**  

`whereRaw(string  $val) : \vitex\ext\object`  

**参数**  

string 	$val 	查询条件语句  

**示例**  

``` 
$this->whereRaw("name='Vitex'")
$this->where('age','>',26)->whereRaw("and name='Vitex'")
```

> **注意** 下面 where系列的方法 默认都是以`and` 连接不同的条件，orWhere系列的方法默认都是用 `or`连接不同的条件。  

### where /orWhere

设置查询条件 where语句   

**签名**   

`where(mixed $key,string $op,string $val) : \vitex\ext\object`   

**参数**   

string $key 键值，字段名  或者一个包含键和值的关联数组 

string $op 操作符 如  = > < >= <= 等   

string $val 值

**示例**   

``` 
$this->where('id','=',1)
$this->where(["id"=>1,"name"=>"vitex"]) => $this->where('id','=',1)->where('name','=','vitex')
```



### whereIn /orWhereIn

查询语句  where in 语句   

**签名**   

`whereIn(string  $key,string $val) : \vitex\ext\object`   

**参数**   

string $key 键值，字段名   

string/array/object $val 值

**示例**   

``` 
$this->whereIn("name","a,b,c") where name in ('a','b','c')  
$this->whereIn("name",['a','b',c]) // 同上  
$this->whereIn('id',\vitex\ext\Model::sub()->from("user")->select('id')) `
where id in (select id from user)
//如果是子查询的whereIn，请确保子查询的代码中不会包含 ,,如果包含 ,可能会导致错误   
```

### whereNotIn / orWhereNotIn

查询语句  where not in 语句   

**签名**   

`whereNotIn(string  $key,string $val) : \vitex\ext\object`   

**参数**   

string $key 键值，字段名   

string/array/object $val 值

**示例**   

``` 
$this->whereNotIn("name","a,b,c") where name not in ('a','b','c')  
$this->whereNotIn("name",['a','b',c]) // 同上  
$this->whereNotIn('id',\vitex\ext\Model::sub()->from("user")->select('id')) 
where id not in (select id from user)
```

> 如果是子查询的whereNotIn，请确保子查询的代码中不会包含 `,`,如果包含 `,`可能会导致错误   

### whereNull  / orWhereNull

查询语句 is null   

**签名**   

`whereNull(string  $key) : \vitex\ext\object`   

**参数**   

string $key 键值，字段名   

**示例**   

`$this->whereNull('name')` // where name is null  

### whereNotNull / orWhereNotNull

查询语句 is not null   

**签名**   

`whereNotNull(string  $key) : \vitex\ext\object`   

**参数**   

string $key 键值，字段名   

string/array/object $val 值   

**示例**   

`$this->whereNotNull('name')` // where name is not null 

### whereExists / orWhereExists

查询语句 EXISTS    

**签名**   

`whereExists(object/string  $key) : \vitex\ext\object`   

**参数**   

string $key 子查询   

**示例**   

``` 
$this->whereExists(\vitex\ext\Model::sub()->from("user")->select('id,name')) 
//where exists (select id,name from user)   
$this->whereExists('select id,name from user')     
```

### whereNotExists / orWhereNotExists

查询语句 NOT EXISTS    

**签名**   

`whereNotExists(object/string  $key) : \vitex\ext\object`   

**参数**   

string $key 子查询   

**示例**   

`$this->whereNotExists(\vitex\ext\Model::sub()->from("user")->select('id,name')) `   

//where not exists (select id,name from user)   

`$this->whereNotExists('select id,name from user') `     

### whereBetween / orWhereBetween

操作符 BETWEEN ... AND 会选取介于两个值之间的数据范围。这些值可以是数值、文本或者日期。   

**签名**   

`whereBetween(string  $key,array $val) : \vitex\ext\object`   

**参数**   

string $key 键值，字段名   

array $val 值,一个包含两个元素的数组， between ele1 and ele2   

**示例**   

`$this->whereBetween('age',[10,20])` between 10 and 20   

### whereNotBetween / orWhereNotBetween

操作符 Not BETWEEN ... AND 会排除介于两个值之间的数据范围。这些值可以是数值、文本或者日期。   

**签名**   

`whereNotBetween(string  $key,array $val) : \vitex\ext\object`   

**参数**   

string $key 键值，字段名   

array $val 值,一个包含两个元素的数组， not between ele1 and ele2   

**示例**   

`$this->whereNotBetween('age',[10,20])` not between 10 and 20   

### findInSet() / orFindInSet()

使用find_in_set方法查找一个字段中是否包含某个值

**签名**

``` 
findInSet(string $column,mixed $val) : \vitex\ext\Model
```

**参数**

string $column 字段名

mixed $val 值

**示例**

``` 
$this->findInSet('pos','a') //pos字段(a,b,c)中包含一个a元素
```

### whereTuple()

一组查询条件，该方法的查询条件会被当做一组条件来处理 会自动添加括号

```
  $this->orWhereTuple(Model::sub()->where("name","=","xx")->where("age","=",100))->orWhere("name","=","john")
  //  =>
    ( name="xx" and age = 100) or name="john"
```


### having()

Having分组操作条件,支持链式操作    

**签名**  

`having(string  $key, string $op, array/callable  $val, string  $type = "AND") : object`  

**参数**  

string 	$key 	键值

string 	$op 	操作符

array/callable 	$val 	操作值

string 	$type 	类型 and/or  

**示例**  

`$this->having('num','>',100,'and')`  

### from()

要查询的表名,支持链式操作    

**签名**  

`from(string  $table) : object`  

**参数**  

string 	$table 	表名  

**示例**  

`$this->from('user')`  

### limit()

查询的条数,支持链式操作         

**签名**  

`limit(string  $limit, integer  $offset) : object`  

**参数**  

string 	$limit 	要查询的条数

integer 	$offset 	偏移值 默认0

**示例**  

`$this->limit(10,2)` // limit 2,10   

`$this->limit(10)`  // limit 10  

### getTable()

获取当前要查询的表名    

**签名**  

`getTable() : string`  

**示例**  

`$table = $this->getTable()`  



### offset()

设置查询的偏移数制,支持链式操作         

**签名**  

`offset(integer  $offset) : object`  

**参数**  

integer 	$offset 	偏移数值  

**示例**  

`$this->limit(10)->offset(4)` // limit 4,10  


### when()

当满足一定条件时才会执行的条件

mixed $condition  需要判定的条件是否成立，PHP if 是否成立
callable $call    条件成立时执行的方法，接受一个参数为当前模型的实例
callbale $notcall 条件不成立时执行的方法，接受一个参数为当前模型的实例
```
    $this->when($age,function($model) use($age){
        $model->where('age','=',$age);
    });
        
    //如果age为 true类型则相当于  select * from user where age = $age;
    //如果age为 false 则相当于 select * from user;
    
        $this->when($age,function($model) use($age){
            $model->where('age','=',$age);
        },function($model) use($age){
            $model->where("age",">",0);
        });
        //如果age为 true类型则相当于  select * from user where age = $age;
        //如果age为 false 则相当于 select * from user where age > 0;
```


### orderBy()

设置排序字段以及排序方式,支持链式操作       

**签名**  

`orderBy(string  $column, string  $way = "DESC") : object`  

**参数**  

string 	$column 	字段

string 	$way 	排序方式  

**示例**  

`$this->orderBy('age','desc')` == `$this->orderBy('age')`  

### groupBy()

group分组操作,支持链式操作        

**签名**  

`groupBy(string  $column) : object`  

**参数**  

string 	$column 	要分组的字段  

**示例**  

`$this->groupBy('name')`  

`$this->select(["count(*) as num","FROM_UNIXTIME( `create_at`, '%H' ) as hour"])`

如上示例，如果本身是个函数，则字段中包含`,`的话一定要用数组的形式 或者多次调用 select 不要直接使用 

`$this->select("count(*) as num,FROM_UNIXTIME( `create_at`, '%H' ) as hour")` 这种形式会构造出错

### distinct()

去重查询,支持链式操作        

**签名**  

`distinct(string/array  $column) : object`

**参数**  

string/array 	$column 	字段名  

**示例**  

`$this->distinct('name')` // select distinct name;  

`$this->distinct(['name','age'])` // select distinct name,age  

### union()

union操作连表查询        

**签名**  

`union(string/callable  $str) : object`  

**参数**  

string/callable 	$str 	union字符串或者一个可以tostring的对象（例如model对象的实例）  

**示例**  

`$this->union('select * from user')`  

`$this->union(\vitex\ext\Model::sub()->from('user'))`  

### set()

修改查询的数据 设置要保存的数据，调用`save`方法时会使用此方法设置的数据      

**签名**  

`set(string  $key, string  $val) : object`  

**参数**  

string/array 	$key 	键值  

string 	$val 	值  

**示例**  

`$this->set('name','Vitex')->save(1)` // 根据主键作为条件保存数据,明确指定主键  

`$this->get(1);$this->set('name','Vitex')->save()` //使用get方法获取的数据的主键  

`$this->set(['name'=>'vitex','age'=>10]).save()`

### beigin()

开始一个事务

**签名**

`beigin():object`

**示例**

`$this->beigin();`

### commit()

提交一个事务

**签名**

`commit():object`

**示例**

`$this->commit()`

`$this->begin();$this->def(["name"=>"vitex"])->save();$this->commit();`

### update()

根据where条件修改内容      

**签名**  

`update(array  $arr) : mixed`  

**参数**  

array $arr 要修改的数据 关联数组，键名为数据库字段名    

**示例**  

`$this->from('user')->where('id','=',1)->update(['name'=>'Vitex'])`  

### insert()

向数据库中插入数据，可以是多维数组； 当为二维数组的时候插入多条数据        

**签名**  

`insert(array  $arr = array()) : mixed`  

**参数**  

array 	$arr 关联数组，一维或者二维数组，键值为数据库字段名  

**示例**  

`$this->insert(['name'=>'Vitex'])`  

`$this->insert([['name'=>'Vitex1'],['name'=>'Vitex2']])`  

//表名默认为类名（小写）

### save()

ORM似的保存 保存当前模型，如果存在主键则尝试修改，如果不存在主键则尝试新建  

**注意** 如果设定了排除字段则设定的排除字段不参与修改    

**签名**  

save(mixed $id) : mixed  

**参数**  

mixed $id  主键的值，保存时的条件，新增加的数据不需要指定该字段   

**示例**  

`$this->name = 'Vitex'; $this->save();`  insert into user (`name`) values ('Vitex');  

### delete()

删除数据       

**签名**  

`delete() : boolean`    

**示例**

`$this->where('id','=',1)->detele()`  

### truncate()

清空指定表中的数据       

**签名**  

`truncate() : object`  

**示例**  

`$this->truncate()` // 默认表  

`$this->from('user')->truncate()`   

### increment()

自增一个字段的值       

**签名**  

`increment(mixed $column, mixed $amount = 1) : boolean`  

**参数**  

mixed 	$column 字段名,可以传递一个字段或者使用数组传递多个字段  

mixed    $amount  自增的数制默认为1 ，当$column为数组的时候则 1. 此字段可以选择传递非数组值，表示 所有字段自增相同的值；2.可以传递一个与$column相同长度的数组，表示不同字段传递不同值一一对应

**示例**  

`$this->increment('pv',1)`  

`$this->from('table')->increment('click',3)`  

`$this->increment(["total","money"],3)`

`$this->increment(["total","money"],[3,5])`

### decrement()

自减一个字段的值       

**签名**  

`decrement(mixed $column, mixed  $amount = 1) : boolean`   

**参数**  

string 	$column 	字段名   ,可以传递一个字段或者使用数组传递多个字段  

integer $amount 自减的数制默认为1,  当$column为数组的时候则 1. 此字段可以选择传递非数组值，表示 所有字段自减相同的值；2.可以传递一个与$column相同长度的数组，表示不同字段传递不同值一一对应 

**示例**  

`$this->decrement('pv',1)`  

`$this->from('table')->decrement('click',3)` 

`$this->decrement(["total","money"],3)`

`$this->decrement(["total","money"],[3,5])`



### count()

统计数量，select count(*) from user            

**签名**  

`count(string  $column = '*') : integer`    

**参数**  

`string $column 字段名`    

**示例**  

`$this->count()` // select count(*) from user   

`$this->from('table')->count('name')`  

### pluck()

一个简化的array_map操作，可以按照指定的字段返回一个仅包含该字段的数组    

**签名**  

`pluck(string  $column) : array`   

### field()

可以返回查出行中指定字段的值

**签名**  

`field(string  $column) : string`   

**参数**  

string 	$column 字段名

**示例**  

`$this->from('user')->where("id","=",1)->field('name')`  

返回一个 "vitex"

### get()

获取一条记录,可以制定一个主键的值，如果不设置则需要使用where系列方法设置条件       

**签名**  

`get(string  $id = null) : mixed`  

**参数**  

string 	$id ID主键值   

**示例**  

`$this->get(1)` // select * from user where id=1   

> getBy.. 是一个系列方法，如果您的数据表中包含指定的字段那么就可以直接使用该方法获取指定字段的内容  

### getBy..

本系列方法会把 `By`后面的内容转为小写然后当做数据库的字段来查询,返回符合条件的单条数据

**示例**  

`$this->getByName('vitex')` // select * from table where name = 'vitex'   

`$this->getByAge(10)` // select * from table where age = 10   



### getAll()

根据查询条件返回数组       

**签名**  

`getAll() : array`   

**示例**  

`$this->where('age','>','18')->getAll()` select * from user where age>18   

> getAllBy.. 是一个系列方法，如果您的数据表中包含指定的字段那么就可以直接使用该方法获取指定字段的内容  

### getAllBy..

本系列方法会把 `By`后面的内容转为小写然后当做数据库的字段来查询,返回符合条件的多条或者单条数据，为一个二维数组   

**示例**  

`$this->getAllByName('vitex')` // select * from table where name = 'vitex'   

`$this->getAllByAge(10)` // select * from table where age = 10   

### max

统计类方法，查询指定字段额最大值

**示例**

`$this->max("age")` //查询最大年龄

### getMeta()

获取表格结构元数据



**示例**

`$this->getMeta()`

**返回数据**
    - field 字段名
    - type  字段类型
    - typeArr 字段类型详情
        - 类型
        - 长度
        - 数据 比如 set类型时候的数据
    - null true 可以为NULL  false 不可以为 NULL    
    - key PRI 主键 索引等
    - default 默认值
    - extra 额外数据

```
Array
(
    [0] => Array
        (
            [field] => id
            [typeArr] => Array
                (
                    [0] => int
                    [1] => 11
                    [2] => 
                )

            [type] => int
            [null] => 
            [key] => PRI
            [default] => 
            [extra] => auto_increment
        )

    [1] => Array
        (
            [field] => userid
            [typeArr] => Array
                (
                    [0] => int
                    [1] => 11
                    [2] => 
                )

            [type] => int
            [null] => 
            [key] => 
            [default] => 
            [extra] => 
        )

    [2] => Array
        (
            [field] => name
            [typeArr] => Array
                (
                    [0] => varchar
                    [1] => 30
                    [2] => 
                )

            [type] => varchar
            [null] => 
            [key] => 
            [default] => 
            [extra] => 
        )

)

```

### min

统计类方法，查询指定字段的最小值

**示例**

`$this->min("age")` // 查询最小年龄值

`$this->where("grade","=","1")->min("age")`

### avg

统计类方法，查询指定字段的平均值

**示例**

`$this->avg("age")` //平均年龄

### sum

统计类方法，查询指定字段的和值

**示例**

`$this->sum("age")` //年龄的和值

### page()

直接按照分页查询相关的信息，包括总页数以及当前分页的内容       

**签名**  

`page( integer $page = 1, integer  $num = 10) : array`   

**参数**  

integer 	$page  页码 默认为1 	  

integer 	$num   每页条数，默认为10  

**返回值**   

[infos,total]  

第一个元素是查询出来的信息具体内容  

第二个元素是当前查询条件下的总条数  

**示例**  

`$this->where('age','>',10)->page()` // select * from user where age > 1 limit 0,10   

`list($lists,$total) = $this->page(10,10)` // select * from user limit 90,10

## 执行原生SQL语句

### fetch

执行一个指定的sql语句，一般为select型的查询语句，此方法返回一条查询的信息。

**签名**

`fetch(string $sql,int $type=\PDO::FETCH_ASSOC):array`

第一个参数为查询的sql语句，第二个参数是返回的数据类型，详情查看pdo文档

**示例**

``` 
$this->fetch("select sum(money) as money from order where uid=1");
```



### fetchAll

执行一个指定的sql语句，一般为select型的查询语句，此方法返回一组查询的信息。

**签名**

`fetchAll(string $sql,int $type=\PDO::FETCH_ASSOC):array`

第一个参数为查询的sql语句，第二个参数是返回的数据类型，详情查看pdo文档

**示例**

``` 
$this->fetchAll("select sum(money) as money from order group by uid");
```

### execute

执行一条没有返回值的非查询语句，如update delete等

**签名**

`execute(string $sql):int`

**示例**

``` 
$this->execute("delete from order where id=1");
```


### setJustSql

设置操作仅仅拼接sql 不会去数据库执行，此方法一般配合 getSql()方法获取拼接的sql

**设置此方法之后仅仅获取sql不会执行**
```
    $this->from('user')->where("id","=",1)->setJustSql()->get();
    $sql = $this->getSql();
    //sql
    select * from user where id=1 limit 1;
```

### 输出调试信息

```
$this->debugDumpParams();
```