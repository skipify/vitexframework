# 分页

分页类主要对多条内容显示提供一个简单的分页展示效果。

## 使用

最简单

``` php
$pagination = new Pagination(["url" => '/search', 'totalrows' => $count]);
$pagestr = $pagination->get();
/*
<a href="/search?page=1" class="firstpage">首页</a>
<a href="javascript:;" class="prevpage">上一页</a>
<a href="javascript:;" class="curpage">1</a>
<a href="/search?page=2" class="pagenum">2</a>
<a href="/search?page=3" class="pagenum">3</a>
<a href="/search?page=4" class="pagenum">4</a>
<a href="/search?page=5" class="pagenum">5</a>
<a href="/search?page=6" class="pagenum">6</a>
<a href="/search?page=2" class="nextpage">下一页</a>
<a href="/search?page=100" class="lastpage">末页</a> 
*/
```

添加包裹元素

``` php
$pagination = new Pagination(["url" => '/search', 'totalrows' => $count]);
$pagestr = $pagination->get("li"); //使用指定的元素包裹分页链接
/*
<li><a href="/search?page=1" class="firstpage">首页</a></li>
<li><a href="javascript:;" class="prevpage">上一页</a></li>
<li><a href="javascript:;" class="curpage">1</a></li>
<li><a href="/search?page=2" class="pagenum">2</a></li>
<li><a href="/search?page=3" class="pagenum">3</a></li>
<li><a href="/search?page=4" class="pagenum">4</a></li>
<li><a href="/search?page=5" class="pagenum">5</a></li>
<li><a href="/search?page=6" class="pagenum">6</a></li>
<li><a href="/search?page=2" class="nextpage">下一页</a></li>
<li><a href="/search?page=100" class="lastpage">末页</a></li>
*/
```

返回数组分页

``` php
$pagination = new Pagination(["url" => '/search', 'totalrows' => $count]);
$pagearr = $pagination->getArray(); //返回一个数组

/*
array (
  0 => '<a href="/search?page=1" class="firstpage">首页</a>',
  1 => '<a href="javascript:;" class="prevpage">上一页</a>',
  2 => '<a href="javascript:;" class="curpage">1</a>',
  3 => '<a href="/search?page=2" class="pagenum">2</a>',
  4 => '<a href="/search?page=3" class="pagenum">3</a>',
  5 => '<a href="/search?page=4" class="pagenum">4</a>',
  6 => '<a href="/search?page=5" class="pagenum">5</a>',
  7 => '<a href="/search?page=6" class="pagenum">6</a>',
  8 => '<a href="/search?page=2" class="nextpage">下一页</a>',
  9 => '<a href="/search?page=100" class="lastpage">末页</a>',
)
*/
```

## 配置项

- linknum 一次分页显示的链接数量，默认为10个
  
- totalpage 总页码这个是分页的基础会使用这个参数来分页（此参数与totalrows参数必须指定一个）
  
- totalrows 要分页的信息总条数，此参数会使用perpage参数来计算totalpage参数的值
  
- perpage 每页显示的信息条数，默认10条
  
- url 分页的链接地址，此项必须指定
  
- param 页码参数键名，会通过get方式获取此参数代表的值当做当前页码 ，默认是page
  
- label 一个数组配置项用于配置第一页，第二页等标签项
  
  ``` 
  $label = [
          'first' => '首页',
          'last'  => '末页',
          'prev'  => '上一页',
          'next'  => '下一页',
      ];
  $label = [
          'first' => 'First',
          'last'  => 'Last',
          'prev'  => 'Prev',
          'next'  => 'Next',
      ];
  ```



## 方法

### get

此方法用于返回分页的结果字符串，可以指定一个html元素名作为参数来包括每一个分页链接

`get(string wrap="") :string`

### getArray

此方法会返回一个分页链接组成的数组

`getArray():array`