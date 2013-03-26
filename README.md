LazyPHP-AR
===========

这是一个专门为 LazyPHP 打造的，轻量级的 Active Record 实现。

需要 LazyPHP 的 db 函数库才能运行。

PHP 5.3.3 测试通过，不保证别的版本。

在 [BSD 协议](http://en.wikipedia.org/wiki/BSD_licenses) 下发布。

特性
-----

- 如遵守默认的命名规则，几乎不需要配置。
- 支持联表查询。
- 自动过滤所有的参数，防止 SQL 注入。
- 支持方法链。

简明教程
---------

请将 `model` 目录下的 `core.class.php` 文件放在 LazyPHP 的 `_lp/core/model` 目录中。
将 `controller` 目录下的 `core.class.php` 文件放在 LazyPHP 的 `_lp/core/controller` 目录中，替换掉同名文件。

Active Record 意味着数据库中的一行数据对应一个对象，而一个表，就对应着一个类。

首先要针对每个表建立相应的类。然后就可以使用非常人性化的方法获取对象的属性了。

new 一个对象，就相当于从表中取了一行数据。

```php
class Book extends CoreModel {}
class Author extends CoreModel {}
$book = new Book($book_id);
echo $book->name;
echo $book->author()->name;
```

查找对象，即获取多个表格行的方法如下：

```php
$books = Book::search()->by('author.name', '曹雪芹')->find();
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

也可以使用不同的运算符获取对象。

```php
$books = Book::search()->by('name', "%$keyword%", 'like')->find();
```

或者使用联表查询，再或者将条件联合起来查询，这一切都是自动的。

分页使用 limit 和 offset 方法。

```php
$searcher = Book::search()
    ->by('author.nationality', 'UK')
    ->by('author.gender', 'female');
$count = $searcher->count();
$books = $searcher->limit(20)->offset(100)->find();
```