<?php

/**
 * 
 * ORM for LazyPHP
 * 
 * https://github.com/picasso250/ORM4LazyPHP
 * 
 * 这是一个专门为 LazyPHP 打造的 Active Record 类
 * Active Record 意味着数据库中的一行数据对应一个对象
 * 
 * 使用方式：
 * $book = new Book($book_id);
 * echo $book->name;
 * echo $book->author()->name;
 * $books = Book::search()->by('author.name', '曹雪芹')->find();
 * 
 * @author picasso250
 */

class CoreModel
{
    protected $id = null;
    protected $info = null;
    
    // new an object from id or array
    // 新建一个 Object
    // 接受的参数是一个id
    public function __construct($a)
    {
        if (is_array($a) && isset($a['id'])) { // new from array
            $this->id = $a['id'];
            $this->info = $a;
        } elseif (is_numeric($a)) { // new from id
            $this->id = $a;
        } elseif (is_object($a) && is_a($a, get_called_class())) { // clone
            $this->id = $a->id;
        } else {
            throw new Exception("not good arg for construct: $a");
        }
    }

    // $info 的形式 array('expression', 'key' => 'value',...)
    public static function create($info = array())
    {
        // 这里主要是为了解决 created=NOW() 的问题
        $keyArr = array();
        $valueArr = array();
        foreach ($info as $key => $value) {
            if (is_object($value) && is_a($value, 'CoreModel')) {
                $value = $value->id;
            }
            if (is_numeric($key)) {
                $t = explode('=', $value);
                $key = $t[0];
                $value = $t[1];
                $valueArr[] = $value;
            } else {
                $valueArr[] = "'".s($value)."'";
            }
            $keyArr[] = "`$key`";
        }
        $sql = 'INSERT INTO `'.self::table().'` ('.implode(',', $keyArr).') VALUES ('.implode(',', $valueArr).')';
        run_sql($sql);
        if (db_errno()) {
            throw new Exception("error when insert: ".db_error(), 1);
        }

        $self = get_called_class();
        return new $self(last_id());
    }

    public function toArray()
    {
        if ($this->info) {
            return $this->info;
        }
        return $this->info();
    }

    private function info()
    {
        $sql = 'SELECT * FROM `'.self::table()."` WHERE `id`='".s($this->id)."' LIMIT 1";
        $ret = get_line($sql);
        if (empty($ret))
            throw new Exception(get_called_class() . " no id: $this->id");
        return $this->info = $ret;
    }

    public function exists()
    {
        $sql = 'SELECT * FROM `'.self::table()."` WHERE `id`='".s($this->id)."' LIMIT 1";
        $ret = get_line($sql);
        if (empty($ret)) {
            return false;
        } else {
            $this->info = $ret;
            return true;
        }
    }

    public static function table()
    {
        if (isset(static::$table)) {
            return static::$table;
        } else {
            return self::camelCaseToUnderscore(get_called_class());
        }
    }

    public function update($a, $value = null)
    {
        $exprArr = array();
        if($value !== null) { // given by key => value
            $exprArr[] = "`$a`='".s($value)."'";
        } elseif (is_array($a)) {
            foreach ($a as $key => $value) {
                if (is_numeric($key)) {
                    $exprArr[] = $value;
                } elseif ($value !== null) {
                    $exprArr[] = "`$key`='".s($value)."'";
                }
            }
        }
        $sql = 'UPDATE `'.self::table().'` SET '.implode(',', $exprArr)." WHERE `id`='".s($this->id)."' LIMIT 1";
        run_sql($sql);
        if (db_errno()) {
            throw new Exception("update error: ".db_error(), 1);
        }
        $self = get_called_class();
        $this->info = $this->info(); // refresh data
    }

    public function __get($name) 
    {
        if ($name === 'id') return $this->id;
        if (empty($this->info))
            $this->info = $this->info();
        $info = $this->info;
        if (is_bool($info)) {
            throw new Exception("info empty, maybe because you have no id: $this->id in " . get_called_class());
        }
        if (!array_key_exists($name, $this->info)) {
            throw new Exception("no '$name' when get in class " . get_called_class());
        }
        return $this->info[$name];
    }

    public function __set($prop, $value)
    {
        $this->update($prop, $value);
    }

    public function __isset($prop)
    {
        $this->info();
        return isset($this->info[$prop]);
    }

    public function get($prop)
    {
        return $this->__get($prop);
    }

    public function __call($name, $args)
    {
        if (empty($this->info)) {
            $this->info = $this->info();
        }
        $prop = self::camelCaseToUnderscore($name);
        if (isset($this->info[$prop])) {
            $class = ucfirst($name);
            return new $class($this->info[$prop]);
        } else {
            throw new Exception("no $prop when call $name", 1);
        }
    }

    public function delete()
    {
        $sql = 'DELETE FROM `'.self::table()."` WHERE `id`='".s($this->id)."' LIMIT 1";
        run_sql($sql);
        if (db_errno()) {
            throw new Exception("delete error: ".db_error(), 1);
        }
    }

    public static function search()
    {
        return new Searcher(get_called_class());
    }

    public static function relationMap()
    {
        $self = get_called_class();
        return isset($self::$relationMap) ? $self::$relationMap : array();
    }

    public function underscoreToCamelCase($value) 
    {
        return implode(array_map(function($value) { return ucfirst($value); }, explode('_', $value)));
    }
     
    public function camelCaseToUnderscore($value) 
    {
        return preg_replace_callback('/([A-Z])/', function($char) { return '_'.strtolower($char[1]); }, lcfirst($value));
    }
}

class Searcher
{
    private $table    = null;
    private $class    = null;
    private $fields   = array();
    private $tables   = array();
    private $conds    = array();
    private $orders   = array();
    private $limit    = 1000;
    private $offset   = 0;
    private $distinct = false;
    
    public function __construct($class)
    {
        $this->class = $class;
        $this->table = $class::table();
        $this->tables[] = $this->table;
    }

    public function table() // ?
    {
        return $this->table;
    }

    /**
     * $book = Book::search()->by('author.name', '曹雪芹');
     * 不支持 OR
     * 不支持 IN/BETWEEN
     * 如果只传一个字符串，那么将会把这个字符串直接当作表达式来用！
     */
    public function by($field, $value = null, $op = '=')
    {
        // 使得用户可以传一个object进来
        // is_object() 判断不可少，不然SAE上会把String也认为Ojbect
        if (is_object($value) && is_a($value, 'CoreModel'))
            $value = $value->id;

        $relationMap = $this->relationMap();
        $tableDotKey = preg_match('/\b(\w+)\.(\w+)\b/', $field, $matches); // table.key

        if ($tableDotKey) {
            $refTable = $matches[1];
            $refKey = $matches[2];
            $foreignKey = $refTable;

            // 如果有特意配置，就可以用外键名当作表名查询
            if (isset($relationMap[$refTable])) {
                $refTable = $relationMap[$refTable];
            }

            if ($value !== null) {
                $cond = "`$refTable`.`$refKey` $op '".s($value)."'";
            } else {
                $cond = "($field)";
            }
            $this->conds[] = $cond;
            $this->conds[] = "`$this->table`.`$foreignKey`=`$refTable`.id"; // join on
            $this->tables[] = $refTable;
            $this->fields[] = "`$refTable`.`$refKey` AS {$refTable}_{$refKey}"; // 既然找到了，就搞上去
            $this->fields[] = "`$refTable`.id AS {$refTable}_id";
        } else {
            $this->conds[] = "`$field` $op '".s($value)."'";
        }
            
        return $this;
    }

    public function sort($exp)
    {
        $this->orders[] = "$this->table.$exp";
        return $this;
    }

    public function limit()
    {
        if (!func_num_args())
            return $this->limit;
        $this->limit = func_get_arg(0);
        return $this;
    }

    public function offset()
    {
        if (!func_num_args())
            return $this->offset;
        $this->offset = func_get_arg(0);
        return $this;
    }

    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    public function find($limit = null, $offset = null)
    {
        if ($this->distinct) {
            $field = "DISTINCT(`$this->table`.id)";
        } else {
            $this->fields[] = "`$this->table`.*";
            $field = implode(',', array_unique($this->fields));
        }
        $tableStr = '`'.implode('`,`', array_unique($this->tables)).'`';
        if ($this->conds) {
            $where = 'WHERE '.implode(' AND ', array_unique($this->conds));
        } else {
            $where = '';
        }
        $orderByStr = $this->orders ? 'ORDER BY '.implode(',', $this->orders) : '';
        if ($limit !== null) {
            $this->limit = $limit;
        }
        if ($offset !== null) {
            $this->offset = $offset;
        }
        $limitStr = $this->limit ? "LIMIT $this->limit" : '';
        $tail = "$limitStr OFFSET $this->offset";
        $sql = "SELECT $field FROM $tableStr $where $orderByStr $tail";
        $results = get_data($sql) ?: array();

        $ret = array();
        foreach ($results as $a) {
            if (count($a) === 1) {
                $a = $a['id'];
            }
            $ret[] = new $this->class($a);
        }
        return $ret;
    }

    public function count()
    {
        $field = "`$this->table`.id";
        if ($this->distinct)
            $field = "DISTINCT($field)";
        $tableStr = implode(',', array_unique($this->tables));
        if ($this->conds) {
            $where = 'WHERE '.implode(' AND ', array_unique($this->conds));
        } else {
            $where = '';
        }
        $sql = "SELECT COUNT($field) FROM $tableStr $where";
        return  get_var($sql);
    }

    // ------------ private section -----------------

    private function relationMap()
    {
        if (isset($this->relationMap))
            return $this->relationMap;
        $class = $this->class;
        return $this->relationMap = $class::relationMap();
    }
}
