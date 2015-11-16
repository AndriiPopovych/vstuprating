<?php
error_reporting(2);
function __autoload($className) {
    $classList = explode("\\", $className);
    $className = end($classList);
    if (file_exists("Models/$className.php")) {
        include_once "Models/$className.php";
    }
    if (file_exists("system/$className.php")) {
        include_once "system/$className.php";
    }
}

$parser = new Parser();
//$parser->parseFields();
//$parser->parseUniversity();
$parser->parseConcreteUniversity();