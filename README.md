LazyPHP-AR
===========

LazyPHP-AR 一个专门为 [LazyPHP](https://github.com/easychen/LazyPHP) 打造的，轻量级的 Active Record 实现。

需要 LazyPHP 的 db 函数库才能运行。

PHP 5.3.3 测试通过，不保证别的版本。如有需要，请提 Issue。

在 [BSD 协议](http://en.wikipedia.org/wiki/BSD_licenses) 下发布。

特性
-----

- 如遵循默认的命名规则，几乎不需要配置。
- 支持联表查询。
- 自动过滤所有的参数，防止 SQL 注入。
- 支持方法链。

安装
-------

1. 将 `model` 目录下的 `core.class.php` 文件放在 LazyPHP 的 `_lp/core/model` 目录中。
2. 将 `controller` 目录下的 `core.class.php` 文件放在 LazyPHP 的 `_lp/core/controller` 目录中，替换掉同名文件。

简明教程
---------

Active Record 意味着数据库中的一行数据对应一个对象，而一个表，就对应着一个类。

首先要针对每个表建立相应的类。

new 一个对象，就相当于从表中取了一行数据。

然后就可以通过获取对象的属性来获取表中的数据了。

```php
class Book extends CoreModel {}
class Author extends CoreModel {}
$book = new Book($book_id);
echo $book->name;
echo $book->author()->name;
```

查找对象，即获取多个表格行的方法如下：

```php
$books = Book::search()
    ->('publish_year', 2000, '>') // 本世纪出版的书
    ->by('author.name', '莫言')   // 作者是莫言，注意，这里已经使用了联表查询
    ->find();
```

创建一个对象，相当于在表中添加一行。

```php
$newBook = Book::create(array(
    'name' => 'Harry Potter and sorry, forget',
    'author' => $author,
    'language' => 'en',
));
```

对一个对象的属性赋值，就相当于 update 表格里的数据。

```php
$newBook->name = 'Harry Potter and the Goblet of Fire';
```

将条件联合起来查询，或者联表查询，这一切都是自动的。

分页使用 limit 和 offset 方法。

```php
$searcher = Book::search()
    ->by('author.nationality', 'UK')
    ->by('author.gender', 'female');
$count = $searcher->count();
$books = $searcher->limit(20)->offset(100)->find();
```

理念
-----

一个优秀的库懂得适可而止。对于一个程序员来说，做一个大而全的东西是很有吸引力的，但是我不接受那个诱惑。

我欣赏 LazyPHP 的懒人智慧。要知道，一个网站，有80%的时间在运行那20%的代码，我就只为你们写那20%的代码。

这个库不支持 out join，不支持 group by，不支持 having 查询……
如果你觉得某项需求确实很常见，请提 Issue（但我不保证实现），或者自己写 SQL 语句。
面对现实吧，我们做的网站真的很简单，超过 10 行的 SQL 语句，只存在于传说之中。

当然，你也可以继承 CoreModel 类，写出更复杂的 AR，如缓存，数据校验等。

文档
-----

**命名规则**

数据库使用下划线命名，而类使用骆驼式命名，类的文件名是类名加 `.class.php`。
如类名为 `VeryImportantPerson`，则表名应为 `very_important_person`，而文件名为 `VeryImportantPerson.class.php`。
一个类要单独写在一个文件里。

一个类的表名也可以单独配置。

```php
class Book extends CoreModel {
    public static $table = 'shu_biao'; // 这里我偏要使用汉语拼音做表名
}
```

所有数据库的主键都是 id。不可以配置。

**外键配置**

外键配置在类的公共静态变量 `$relationMap` 里。键名是列名，键值是表名。

```php
class Book extends CoreModel {
    public static $relationMap = array(
        'foreign_key' => 'foreign_table',
        'author_id' => 'author',
        //'publisher' => 'publisher', // 如名字相同，则不需要配置
    );
}
```

如果键名和对应的表名是相同的，如第三行的 `publisher`，则不需要在 `$relationMap` 中配置。
这样更方便，推荐采用这种命名方式。

**获取一行数据**

```php
$book = new Book($id); // 此时不去检测数据是否存在
echo $book->name;      // 直到获取数据时才开始 SQL 查询
echo $book->get('name');

$arr = $book->toArray(); // 转换成数组

// 不需要自己写 author() 方法，只要定义了 Author 类即可。
// 这个 $author 对象有 Author 类的所有方法
$author = $book->author(); 

$author->exists()       // 判断数据是否存在
```

**创建一行数据**
```php
$newBook = Book::create(array(
    'name' => 'Harry Potter and sorry, forget',
    'author' => $author, // 此处可以使用对象赋值，将自动转换成它的 id
    'language' => 'en',
    'created=NOW()',     // 可以直接写表达式
));
```

**更新数据**

```php
$book->name = 'new name'; // 即刻访问数据库，生效

// 更新多列
$book->update(array(
    'name' => 'another new name',
    'click=click+1',     // 也可以使用表达式哦
));
```

**删除数据**

```php
$book->delete();
```

**查询**

```php
$searcher = Book::search()
    ->by('author.nationality', 'UK')
    ->by('author.gender', 'female')
    ->by('publish_year', 1999, '>') // 支持不同的运算符
$count = $searcher->count();
$books = $searcher->limit(20)->offset(100)->find();
$books = $searcher->find(20, 100); // 和上一行语句的作用一样

// 支持直接传字符串作为复杂表达式，但需要自己过滤
Book::search()->by("author.id='".s($id1)."' OR author.id='".s($id2)."'")->find();
```

