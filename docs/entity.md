#实体的使用
生成实体类的快捷方式

```bash
 php cts.phar model -host=127.0.0.1 -username=root -password=root -database=test -table=test
```


一个使用集合注解的例子


老师实体

```php
namespace app\entity;


use vitex\core\attribute\model\Collection;
use vitex\core\attribute\model\Table;
use vitex\service\model\Entity;
#[Table("teacher")]

class Teacher extends Entity
{
    public int $id;

    public string $name;

    public string $course;

    #[Collection([
        'id' => 'sid',
        'name' => 'sname',
        'grade' => 'grade'
    ],Student::class)]
    public array $students;
}
```

学生实体

```php
<?php


namespace app\entity;


use vitex\core\attribute\model\Association;
use vitex\core\attribute\model\Table;
use vitex\service\model\Entity;

#[Table("student")]
class Student extends Entity
{
    public int $id;

    public int $tid;

    public string $name;

    public int $grade;

    #[Association(
        [
            'id' =>'tid',
            'name' => 'tname'
        ]
    )]
    public Teacher $teacher;
}
```

控制器执行
```php
#[Route("/school")]
class School extends Controller
{
    #[Get("/index")]
    public function index()
    {
        $student = new Student();
        $model = (new Model())->select('s.id,s.name,s.grade,s.tid,t.name as tname')
                        ->from('student s')
                        ->leftJoin('teacher t','t.id','=','s.tid')
                        ->where('s.id','=',1);
        $student = $student->getByModel($model);
        print_r($student->toArray());


        $teacher = new Teacher();
        $model = (new Model())->select('t.id,t.name,t.course,s.grade,s.id as sid,s.name as sname')
                            ->from('teacher t')
                            ->leftJoin('student s','t.id','=','s.tid')
                            ->where('t.id','=',1);

        $teacher = $teacher->getAllByModel($model);
        print_r($teacher->toArray());
    }
}
```

输出结果
```
Array
(
    [id] => 1
    [tid] => 1
    [name] => 张三
    [grade] => 98
    [teacher] => Array
        (
            [id] => 1
            [name] => 刘老师
        )

)
Array
(
    [id] => 1
    [name] => 刘老师
    [course] => 语文
    [students] => Array
        (
            [0] => Array
                (
                    [id] => 1
                    [name] => 张三
                    [grade] => 98
                )

            [1] => Array
                (
                    [id] => 2
                    [name] => 李四
                    [grade] => 95
                )

            [2] => Array
                (
                    [id] => 3
                    [name] => 王五
                    [grade] => 89
                )

            [3] => Array
                (
                    [id] => 4
                    [name] => 马六
                    [grade] => 89
                )

        )

)


```