# 数据过滤

此类提供数据过滤类,此类可能会破坏源数据的完整性,使用之前请注意.此类仅提供简单的过滤,对于复杂的过滤请使用

**注意**  此类方法均为静态方法

## alnum

返回仅包含数字/字母(不区分大小写)的数据,除非您明确知道当前参数仅可以接受数字字母的形式之外请不要使用此方法过滤

```
    $var = "adasd009__-";
    echo Filter::alnum($var); // adasg009

```

## alpha

返回近包含字母(不区分大小)的数据,除非您明确知道此参数仅可以接受字母形式否则不要使用此方法

```
$val = "adasd99++";
echo Filter::alpha($val); // adasd
```

## number

返回数字类型的数据,可能包含 . ,以及数字

```
$val = "adads123,456.34";

echo Filter::number($val); // 123,456.34

```

## int

仅仅返回数字

```
$var = "a008971nnsd0fs";
echo Filter::int($var); // 0089710
```

## safe 

转义 <>&'"为url编码形式

```
$var = "<script>asddas'";
Filter::safe($var) == htmlspecialchars($var)
```

## addslashes

转义 \'"

```
$var = "\89'";
Filter::addslashes($var) == addslashes($var);
```