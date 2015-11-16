<?php
/**
 * Created by PhpStorm.
 * User: Андрій
 * Date: 16.11.2015
 * Time: 16:09
 */

class University extends \Balon\System\iDBable {
    public $code;
    protected $table = "university";

    public $idUniversity;
    public $title;
    public $link;
}