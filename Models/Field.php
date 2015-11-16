<?php
/**
 * Created by PhpStorm.
 * User: Андрій
 * Date: 16.11.2015
 * Time: 13:19
 */

class Field extends \Balon\System\iDBable{
    protected $table = "fields";

    public $idField;
    public $title;
    public $link;
    public $code;
    public $type;
}