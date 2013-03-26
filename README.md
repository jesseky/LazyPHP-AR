ORM4LazyPHP
===========

这是一个专门为 LazyPHP 打造的 Active Record 类

Active Record 意味着数据库中的一行数据对应一个对象

请将model中的core.class.php文件放在lp的core/model目录中，
将controller中的core.class.php文件放在lp的core/controller目录中，替换

使用方式：
---------

```php
class Book extends CoreModel {}
class Author extends CoreModel {}
$book = new Book($book_id);
echo $book->name;
echo $book->author()->name;
$books = Book::search()->by('author.name', '曹雪芹')->find();
```