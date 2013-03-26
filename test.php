<?php

if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

define('AROOT', dirname(__FILE__).DS);
define('CROOT', AROOT);

error_reporting(E_ALL);
ini_set('display_errors' , true);

require_once CROOT.'lib'.DS.'core.function.php';
require_once CROOT.'lib'.DS.'db.function.php';
require_once CROOT.'model'.DS.'core.class.php';

class Book extends CoreModel {
    public static $relationMap = array(
        'author' => 'author',
    );
}
class Author extends CoreModel {}

$book = new Book(1);
echo "$book->name<br>\n";
echo_last_sql();
echo $book->author()->name, "<br>\n";
echo_last_sql();

$newBook = Book::create();


// $books = Book::search()->by('author.name', 'J. K. Rowling')->find();
// echo_last_sql();
// foreach ($books as $book) {
//     echo "$book->name by J. K. Rowling<br>\n";
// }

function echo_last_sql()
{
    echo g('LP_LAST_SQL'), "<br>\n";
}