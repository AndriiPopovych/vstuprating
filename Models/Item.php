<?php
/**
 * Created by PhpStorm.
 * User: Андрій
 * Date: 16.11.2015
 * Time: 18:09
 */

class Item  extends \Balon\System\iDBable{

    protected $table = "items";

    public $idItem;
    public $description;
    public $link;
    public $zno;
    public $idVNZ;
    public $licensedVolume;
    public $governmentOrder;
}