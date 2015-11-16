<?php
/**
 * Created by PhpStorm.
 * User: Андрій
 * Date: 13.09.2015
 * Time: 20:13
 */
namespace Balon\System;

use Balon\DB;
use Balon\DBProc;

abstract class iDBable {
    protected $table;
    protected $pk;
    protected $reference;

    protected $db;
    //abstract public function getTable();

    public function getTable() {
        return $this->table;
    }

    function __construct()
    {
        $this->db = DBProc::instance();
        // TODO: Implement __construct() method.
    }


    public function getPK() {
        return $this->pk;
    }

    public function post()
    {
        $object = new static();
        if ($_GET) {
            foreach ($_GET as $key => $value) {
                if ($value) {
                    $object->$key = $value;
                }
            }
            $db = DB::instance();
            return $db->insert($object);
        }
    }

    public function put()
    {
        $object = new static();
        foreach ($_GET as $key => $value) {
            if ($value) {
                $object->$key = $value;
            }
        }
        try {
            $db = DB::instance();
            return $db->updateObject($object);
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function get()
    {
        $obj = new static();
        foreach ($_GET as $key => $value) {
            $obj->$key = $value;
        }
        $db = DB::instance();
        $db->getObject($obj);
        if ($obj->reference['one']) {
            foreach ($obj->reference['one'] as $key => $val) {
                $modelName = "\\Model\\".$val;
                $obj->$key = new $modelName();
                $obj->{strtolower($val)} = $obj->$key;
                unset($obj->$key);
                $db->getObject($obj->{strtolower($val)});
            }
        }
        elseif ($obj->reference['many']) {
            foreach ($obj->reference['many'] as $key => $val) {
                $modelName = "\\Model\\".$val;
                $obj->$key = new $modelName();
                $obj->$key = $obj->$key->getAll();
                $db->getAll($obj->$key);
            }
        }
        elseif ($obj->reference['manytomany']) {
            foreach ($obj->reference['manytomany'] as $key => $val) {
                $modelName = "\\Model\\".$val;
                $obj->$key = new $modelName();
                $tableMy = $this->getTable();
                $tableSecond = $obj->$key->getTable();
                $dbProc = DBProc::instance();
                $innerTable = "{$tableMy}_has_{$tableSecond}";
                $array = $dbProc->send_query("");
            }
        }
        return  $obj;
    }

    public static function getAll($array = [])
    {
        if (!$array) $array = $_GET;
        $db = DB::instance();
        $className = end(explode("\\", get_class(new static)));
        return $db->select($className, $array);
    }
}