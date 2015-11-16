<?php
/**
 * Created by PhpStorm.
 * User: Андрій
 * Date: 16.11.2015
 * Time: 13:00
 */

class Parser {
    public $url = "http://vstup.info/";
    public $year = "2015";

    public function parseFields()
    {
        $content = file_get_contents("http://vstup.info/");
        preg_match_all("/<table id=\"okr1t\".*>.*<tbody>(.*)<\/tbody><\/table>/Uis", $content, $results);
        preg_match_all("/<tr>(.*)<\/tr>/Uis", $results[1][0], $result);
//        unset($result[0]);
        $fieldList = [];
        $db = \Balon\DB::instance();
        foreach ($result[1] as $key => $value) {
            $field = new Field();
            preg_match_all("/<td>(.*)<\/td>/Uis", $value, $tdList);
            $field->code = $tdList[1][0];
            preg_match_all("/.*<a.*href=.*\"(.*)\".*>(.*)<\/a>.*/Uis", $tdList[1][1], $arr);
            $field->link = $arr[1][0];
            $field->title = $arr[2][0];
            $field->type = "okr1";
            $db->insert($field);
        }

    }


    public function parseUniversity() {
        $universityList = [];
        echo "start parsing university...";
        $db = \Balon\DB::instance();
        for ($i = 3; $i < 35; $i++) {
            $link = "http://vstup.info/2015/i2015a$i.html#abe";
            $page = file_get_contents($link);
            preg_match_all("/<table id=\"vnzt0\".*>.*<tbody>(.*)<\/tbody><\/table>/Uis", $page, $results);
            if ($results[1][0]) {
                preg_match_all("/<tr>(.*)<\/tr>/Uis", $results[1][0], $result);
                unset($result[0]);
                $fieldList = [];
                foreach ($result[1] as $key => $value) {
                    $university = new University();
                    preg_match_all("/<td>(.*)<\/td>/Uis", $value, $tdList);
                    preg_match_all("/.*<a.*href=.*\"(.*)\".*>(.*)<\/a>.*/Uis", $tdList[1][0], $arr);
                    $university->link = $arr[1][0];
                    preg_match_all("/i2015i(.*)\./", $university->link, $a);
                    $university->code = $a[1][0];
                    $university->title = $arr[2][0];
                    $db->insert($university);
                }
            }
        }
        echo "success!";
    }

    public function parseConcreteUniversity() {
        echo "Hello";
        $db = \Balon\DBProc::instance();
        $university =  $db->select("university", false, ["link" => "./i2015i174.html#vnz"])[0];
        print_r($university);
        $content = file_get_contents($this->url .$this->year."/./i2015i174.html#vnz");
        preg_match_all("/<div.*id=\"den-". $university['code'] ."\".*>(.*)<\/div>/Uis", $content, $div);
        $div = $div[1][0];
        preg_match_all("/<ul.*id=\"myTab\">.*<li.*>.*<a href=\"#(.*)\".*>бакалавр.*<\/a>/Uis", $div, $ul);
        $idTab = $ul[1][0];
        preg_match_all("/<div.*id=\"$idTab\">.*<tbody>(.*)<\/tbody>/is", $div, $tbody);
//        echo $tbody[1][0];
        preg_match_all("/(<tr>.*<\/tr>)/Uis", $tbody[1][0], $trArray);
        foreach ($trArray[1] as $key => $tr) {
            preg_match_all("/<td>(.*)<\/td>/Uis", $tr, $td);
            $item = new Item();
            $item->idVNZ = $university->idUniversity;
            $item->description = $td[1][0];
            preg_match_all("", )
            $item->a = $td[1][2];
            $item->b = $td[1][3];
            $item->idVNZ = $td[1][4];
            print_r($td[1]);
            die();
        }
        print_r ($trArray[1]);
    }
}