<?php
/**
 * Created by PhpStorm.
 * User: Андрій
 * Date: 13.09.2015
 * Time: 19:40
 */

namespace Balon;

use Balon\System\iDBable;

class DB extends DBProc{

    static $db = null;

    public static function instance()
    {
        if (!self::$db) {
            self::$db = new self();
        }
        return self::$db;
    }

    private function __construct()
    {
        //parent::instance();
        // TODO: Implement __construct() method.
    }


    public function insert(iDBable $object) {
        $table = $object->getTable();
        $array = [];
        foreach ($object as $key => $val) {
            $array[$key] = $val;
        }
        try {
            return parent::insert($table, $array, true);
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function select($className, $where)
    {
        $className = "\\Model\\".$className;
        $object =  new $className();
        $result = [];
        if (is_subclass_of($object, "Balon\\System\\iDBable")) {
            $table = $object->getTable();
            try {
                $array = parent::select($table, false, $where);
                foreach ($array as $obj) {
                    $result[] = (object) $obj;
                }
                return $result;
            }
            catch (\Exception $e) {
                return null;
            }
        }
    }

    public function getObject(iDBable &$object)
    {
        $table = $object->getTable();
        $array = [];
        foreach ($object as $key => $val) {
            if ($val) {
                $array[$key] = $val;
            }
        }
        try {
            $result = parent::select($table,false, $array)[0];
            if ($result) {
                foreach ($result as  $key => $val) {
                    $object->$key = $val;
                }
            }
            else {
                $object = null;
            }
        }
        catch (\Exception $e) {
            //echo $e->getMessage();
            return false;
        }
        return null;
    }

    public function updateObject(iDBable &$object)
    {
        $table = $object->getTable();
        $array = [];
        foreach ($object as $key => $val) {
            if ($val) {
                $array[$key] = $val;
            }
        }
        try {
            parent::update($table, $array, [ $object->getPK() => $object->{$object->getPK()}]);
            return true;
        }
        catch (\Exception $e) {
            //echo $e->getMessage();
            return false;
        }
    }


}