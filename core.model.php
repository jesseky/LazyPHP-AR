<?php

/**
 * @author picasso250
 */

require_once CROOT.'model'.DS.'core.searcher.php';

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

    protected function info() // will this bug?
    {
        $self = get_called_class();
        $sql = 'SELECT * FROM '.self::table().' WHERE id='.s($this->id).'LIMIT 1';
        $ret = get_line($sql);
        if (empty($ret))
            throw new Exception(get_called_class() . " no id: $this->id");
        return $this->info = $ret;
    }

    public function exists()
    {
        return false !== get_var('SELECT id FROM'.self::table().' WHERE id='.s($this->id).'LIMIT 1');
    }

    public static function table()
    {
        $self = get_called_class();
        if (isset($self::$table))
            return $self::$table;
        else 
            return camel2under($self); // camal to underscore, where is this function
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
}
