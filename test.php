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

echo '<meta charset="UTF-8" />', "\n";

title('__get()');
$book = new Book(1);
echo "$book->name<br>\n";

title('__call()');
$author = $book->author();
echo $author->name, "<br>\n";

title('create()');
$newBook = Book::create(array(
    'name' => 'Harry Potter and sorry, forget',
    'author' => $author,
    'language' => 'en',
));
echo "$newBook->name<br>";

title('update()');
$newBook->update('name', 'Harry Potter and the Goblet of Fire');
$newBook->visit();
echo "$newBook->name<br>";

title('find(key=value)');
$lang = 'en';
$books = Book::search()->by('language', $lang)->find();
foreach ($books as $book) {
    echo "$book->name is write in $lang<br>\n";
}

title('find(key like value)');
$keyword = 'harry';
$books = Book::search()->by('name', "%$keyword%", 'like')->find();
foreach ($books as $book) {
    echo "$book->name is about $keyword<br>\n";
}

title('find(foreign.key=value)');
$books = Book::search()->by('author.name', 'J. K. Rowling')->find();
foreach ($books as $book) {
    echo "$book->name by J. K. Rowling<br>\n";
}

title('find(foreign.key1=value1 and foreign.key2=value2)');
$searcher = Book::search()
    ->by('author.nationality', 'UK')
    ->by('author.gender', 'female');
$books = $searcher->limit(2)->find();
foreach ($books as $book) {
    echo "$book->name by UK female writers<br>\n";
}

title('find');
$count = $searcher->count();
echo "actually, there are $count book written by UK female writers";

function echo_last_sql()
{
    echo g('LP_LAST_SQL'), "<br>\n";
}

function title($str)
{
    echo "<h3>$str</h3>\n";
}