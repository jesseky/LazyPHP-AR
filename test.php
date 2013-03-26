<?php

if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

define('AROOT', dirname(__FILE__).DS);
define('CROOT', AROOT);

error_reporting(E_ALL);
ini_set('display_errors' , true);

require_once CROOT.'lib'.DS.'core.function.php';
require_once CROOT.'lib'.DS.'db.function.php';
require_once CROOT.'model'.DS.'core.class.php';

$init_sqls = explode(';', file_get_contents('test.sql'));
foreach ($init_sqls as $sql) {
    $sql = trim($sql);
    if (empty($sql)) {
        break;
    }
    run_sql($sql);
    if (db_errno()) {
        echo db_error();
        exit;
    }
}

class Book extends CoreModel {
    public static $relationMap = array(
        'author' => 'author',
    );

    public static function create($info)
    {
        $info[] = 'created=NOW()';
        return parent::create($info);
    }

    public function visit()
    {
        $this->update(array(
            'hit=hit+1',
            'visited=NOW()'
        ));
    }
}
class Author extends CoreModel {}

$book = new Book(1);
echo "$book->name<br>\n";
echo $book->author()->name, "<br>\n";

$newAuthor = Author::create(array(
    'name' => '曹雪芹',
));

$newBook = Book::create(array(
    'name' => '红楼梦',
    'author' => $newAuthor,
    'language' => 'zh',
));

$newBook->update('name', '红楼梦 前八十回');

$newBook->visit();

$books = Book::search()->by('language', 'en')->find();
foreach ($books as $book) {
    echo "$book->name is write in en<br>\n";
}
// $books = Book::search()->by('author.name', 'J. K. Rowling')->find();
// echo_last_sql();
// foreach ($books as $book) {
//     echo "$book->name by J. K. Rowling<br>\n";
// }

function echo_last_sql()
{
    echo g('LP_LAST_SQL'), "<br>\n";
}