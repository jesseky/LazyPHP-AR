<?php

/**
 * 
 * ORM for LazyPHP
 * 
 * https://github.com/picasso250/ORM4LazyPHP
 * 
 * 这是一个为 LazyPHP 打造的
 * @author picasso250
 */

class CoreModel
{
    protected $id = null;
    protected $info = null;
    
    // new an object from id or array
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

    public static function create($info = array())
    {
        // given by array('key=?' => 'value', 'key' => 'value',...)
        $keyArr = array();
        $valueArr = array();
        foreach ($info as $key => $value) {
            $keyArr[] = (strpos($key, '=') === false) ? "$key=?s" : $key;
            if ($value !== null) {
                $valueArr[] = s($value);
            }
        }
        $sql = 'INSERT INTO '.self::table().' ('.implode(',', $keyArr).') VALUES ('.implode(',', $valueArr).')';
        run_sql($sql);
        if (db_errno()) {
            throw new Exception("error when insert: ".db_error(), 1);
        }

        $self = get_called_class();
        return new $self(last_id());
    }

    // will we need that?
    protected static function notNull($info)
    {
        return array_filter(array_values($info), function ($e) {
            return ($e !== null && $e !== false);
        });
    }

    public function toArray()
    {
        return $this->info();
    }

    private function info()
    {
        $self = get_called_class();
        $sql = 'SELECT * FROM '.self::table().' WHERE id='.s($this->id).' LIMIT 1';
        $ret = get_line($sql);
        if (empty($ret))
            throw new Exception(get_called_class() . " no id: $this->id");
        return $this->info = $ret;
    }

    public function exists()
    {
        return false !== get_var('SELECT id FROM'.self::table().' WHERE id='.s($this->id).' LIMIT 1');
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
        $sql = 'UPDATE '.self::table().' SET ';
        if($value !== null) { // given by key => value
            $sql .= "$a=".s($value);
        } else {
            foreach ($a as $key => $value) {
                $sql .= (strpos($key, '=') === false) ? "$key=?" : $key;
                if ($value !== null) {
                    $sql .= s($value);
                }
            }
        }
        $sql .= ' WHERE id='.s($this->id);
        run_sql($sql);
        if (db_errno()) {
            throw new Exception("update error: ".$db_error(), 1);
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
        $prop = camel2under($name);
        if (isset($this->info[$prop])) {
            $class = ucfirst($name);
            return new $class($this->info[$prop]);
        } else {
            throw new Exception("no $prop when call $name", 1);
        }
    }

    public function del()
    {
        $sql = 'DELETE FROM '.self::table().' WHERE id='.s($this->id);
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
    private $table = null;
    private $class = null;
    private $tables = array();
    private $conds = array();
    private $orders = array();
    private $limit = 1000;
    private $offset = 0;
    private $distinct = false;
    
    public function __construct($class)
    {
        $this->class = $class;
        $this->table = $class::table();
        $this->tables[] = $this->table;
    }

    public function table()
    {
        return $this->table;
    }


    /**
     * $book = Book::search()->by('author.name', '曹雪芹');
     * 不支持 OR
     * 不支持 IN/BETWEEN
     */
    public function by($field, $value, $op = '=')
    {
        // 使得用户可以传一个object进来
        // is_object() 判断不可少，不然SAE上会把String也认为Ojbect
        if (is_object($value) && is_a($value, 'CoreModel'))
            $value = $value->id;

        $relationMap = $this->relationMap();
        $tableDotKey = preg_match('/\b(\w+)\.(\w+)\b/', $field, $matches); // table.key = ?
        $tableDotId = isset($relationMap[$field]);

        if ($tableDotKey) {
            $ref = $matches[1];
            $refKey = $matches[2];
            $refTable = $relationMap[$ref];
            $this->conds[] = "$refTable.$refKey".$op.s($value);
            $this->conds[] = "$this->table.$ref=$refTable.id"; // join on
        } else {
            $this->conds[] = $field.$op.s($value);
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

    /**
     * 这个函数没有用啊，删掉啊
     */
    public function join(Searcher $s)
    {
        $st = $s->table();
        $rMap = array_flip($s->relationMap());
        $refKey = $rMap[$this->table];
        $this->conds[] = "$st.$refKey=$this->table.id";
        $this->conds += $s->conds;

        if (!in_array($st, $this->tables))
            $this->tables[] = $st;
        return $this;
    }

    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    public function find()
    {
        $field = "$this->table.id";
        if ($this->distinct)
            $field = "DISTINCT($field)";
        $tableStr = implode(',', $this->tables);
        if ($this->conds) {
            $where = 'WHERE '.implode(' AND ', $this->conds);
        } else {
            $where = '';
        }
        $orderByStr = $this->orders ? 'ORDER BY '.implode(',', $this->orders) : '';
        $limitStr = $this->limit ? "LIMIT $this->limit" : '';
        $tail = "$limitStr OFFSET $this->offset";
        $sql = "SELECT $field FROM $tableStr $where $orderByStr $tail";
        $ids = get_data($sql);

        $ret = array();
        foreach ($ids as $a) {
            $ret[] = new $this->class($a['id']);
        }
        return $ret;
    }

    public function count()
    {
        $field = "$this->table.id";
        if ($this->distinct)
            $field = "DISTINCT($field)";
        $tableStr = implode(',', $this->tables);
        if ($this->conds) {
            $where = 'WHERE '.implode(' AND ', $this->conds);
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
