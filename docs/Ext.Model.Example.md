#基本应用
## 测试用表：  

    CREATE TABLE `user` (
      `id` int(11) NOT NULL,
      `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
      `age` int(11) NOT NULL,
      `address` varchar(50) COLLATE utf8_unicode_ci NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

## 增加数据

    $user = new \App\Model\User();
    $user->name = 'Vitex';
    $user->age = 18;
    $user->address = 'china';
    $user->save();

    $user = new \App\Model\User();
    $user->def(['name' => 'vitex2', 'age' => 20, 'address' => 'Beijing'])->save();

## 查询数据
  
**主键**查询  
  
    $user->get(1); //select * from user where id = 1; //主键查询

**字段**查询
    
    $user->getByName('Vitex') // select * from user where name = 'vitex' limit 1
    $user->getByAge(18) // select * from user where age = 18 limit 1;  
**多条记录**查询

    $user->getAll();  //select * from user
    $user->getAllByAge(18) ; // select * from user where age = 18
    $user->where('name','=','vitex')->orWhere('age','>',10)->getAll();
    //select * from user where name = 'vitex' or age > 10
    $user->where('name','like','%vi%')->getAll();

更多where类型的条件查询请查看[API](Ext.model.html)

**数量查询**

    $user->count()  //select count(*) from user; 此方法直接返回数字
    $user->count('id')  //select count(`id`) from user; 此方法直接返回数字
    $user->where('age', '>', 10)// select count(*) from user where age > 10
**按分页查询**  
    
    list($info,$total) = $user->where('age','>',10)->page(1,10) 
    //select * from user where age > 10 limit 0,10
    // select count(*) from user where age > 10 
    相当于两条查询，此方法返回查询的数据和总条数 [infos,total]

**直接返回字段数组**

    $user->where('age','>',18)->limit(10)->pluck('name'); // select * from user where age > 18 limit 10;
    该方法直接返回指定字段组成的数组 如  ['vitex1','vitex2']

## 修改数据
**常规修改**   
*简易修改*   

    $user->set('name','Vitex2')->set('age',18)->save(1) 
    // update user set name='vitex2',age=18 where id = 1

    $user->name='vitex2';
    $user->age = 18;
    $user->save(1); //如上
*根据条件修改*

    $user->where('age','=',18)->update(['name'=>'men']) // update user set name='men' where age = 18

*自增/减修改*  

    $user->where('id','=',1)->increment('age',1); //update `user` set `age` = (`age` + 1) where `id` = 1   
    $user->where('id','=',1)->decrement('age',1); //update `user` set `age` = (`age` - 1) where `id` = 1   


**查询修改**

    $user = new \App\Model\User();
    $info = $user->get(1); //select * from user where id = 1
    print_r($info);
    /*Array
    (
        [id] => 1
        [name] => Vitex
        [age] => 18
        [address] => china
    )*/
    $user->name = 'editVitex';
    $user->save(); update user set name = 'editVitex' where id = 1
    print_r($user->get(1));
    /*Array
    (
        [id] => 1
        [name] => editVitex
        [age] => 18
        [address] => china
    )*/

## 删除数据  
*常规删除*  

    $user->where('id','=',1)->delete(); delete from user where id=1
*查询删除*  

    $info = $user->get(1); //select * from user where id=1
    $user->delete();   // delete from user where id = 1   
按照`主键`查询后可以直接简易删除词条信息，删除条件就是删除主键指定的值   

#高级应用

**子查询**  

    $user = new User();
    $user->whereIn('id', User::sub()
                            ->from('user')
                            ->select('id')
                            ->where('age', '>', '1'))
    ->whereExists(User::sub()
                        ->from('user')
                        ->select('name')
                        ->where('name', '=', 'vitex'))
    ->getAll();

    //select * from `user` where `id` in (select `id` from `user` where `age` > 1 ) and exists(select `name` from `user` where `name` = 'vitex' )

**连表查询**

    $user = new User();
    $user->leftJoin('article a', 'user.id', '=', 'a.uid')->where('user.age', '>', 10)->getAll();
    //select * from `user` left join `article` as `a` on `user`.`id` = `a`.`uid` where `user`.`age` > 10

此外还有 rightJoin innerJoin 等等查看 [API](Ext.model.html)  

