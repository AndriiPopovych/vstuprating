<?php
/**
 * Created by PhpStorm.
 * User: Андрій
 * Date: 19.11.2015
 * Time: 15:28
 */

class Competition extends \Balon\System\iDBable{
    protected $table = "competition";

    public $id;
    public $idItem;
    public $name;
    public $priority;
    public $sum;
    public $meanPoint;
    public $zno;
    public $exams;
    public $extraPoints;
    public $ofCompetition;
    public $primary;
    public $targetDirection;
    public $originalDocuments;
}