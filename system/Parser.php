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
        $db_new = \Balon\DB::instance();
        //$db = \Balon\DBProc::instance();
        //$university = $db->select("university", false, ["link" => "./i2015i174.html#vnz"])[0];
        $universities = $db_new->parentSelect("university", false, false, false, false, [0, 300]);
        $u = 0;
        $c = count($universities);
        foreach ($universities as $university) {
            //echo "Start parsing university number # $u \n";
            $u++;
            $content = file_get_contents($this->url . $this->year . "/" . $university['link']);
            preg_match_all("/<div.*id=\"den-" . $university['code'] . "\".*>(.*)<\/div>/Uis", $content, $div);
            $div = $div[1][0];
            preg_match_all("/<ul.*id=\"myTab\">.*<li.*>.*<a href=\"#(.*)\".*>бакалавр.*<\/a>/Uis", $div, $ul);
            $idTab = $ul[1][0];
            preg_match_all("/<div.*id=\"$idTab\">.*<tbody>(.*)<\/tbody>/is", $div, $tbody);
            preg_match_all("/(<tr>.*<\/tr>)/Uis", $tbody[1][0], $trArray);
            foreach ($trArray[1] as $key => $tr) {
                preg_match_all("/<td>(.*)<\/td>/Uis", $tr, $td);
                $item = new Item();
                $item->idVNZ = $university['idUniversity'];
                $item->description = $td[1][0];
//                print_r ($td[1][1]);
                preg_match_all("/.*<a.*href=\"(.*)\".*>.*/", $td[1][1], $link);
                $item->link = $link[1][0];
                if ($item->link != null) {
                    $item->licensedVolume = $td[1][2];
                    $item->governmentOrder = $td[1][3];
                    $item->zno = $td[1][4];
                    $db_new->insert($item);
                }

            }
            echo "Complete ". round($u * 100 / $c, 3)."% \n";
        }
    }

    public function parseConcreteItem() {
        echo "Hi";
        $db = \Balon\DB::instance();
        $items = $db->parentSelect("items", false, false, false, false, [0, 10000]);
        $i = 0;
        $count = count($items);
        foreach ($items as $item) {
            $url = "http://vstup.info/2015/".$item['link'];
            $content = file_get_contents($url);
            preg_match_all("/<tr title=\"Зараховано. Вітаємо!\">(.*)<\/tr>/Uisu", $content, $trArray);
            if (!empty($trArray[1])) {
                foreach ($trArray[1] as $tr) {
                    preg_match_all("/<td.*>(.*)<\/td>/Uisu", $tr, $td);
                    $comp = new Competition();
                    $td = $td[1];
                    $comp->idItem = $item['idItem'];
                    $comp->name = $td[1];
                    $comp->priority = $td[2];
                    $comp->sum = $td[3];
                    $comp->meanPoint = $td[4];
                    $comp->zno = $td[5];
                    $comp->exams = $td[6];
                    $comp->extraPoints = $td[7];
                    $comp->ofCompetition = $td[8];
                    $comp->primary = $td[9];
                    $comp->targetDirection = $td[10];
                    $comp->originalDocuments = $td[11];
                    $db->insert($comp);
                }
            }
            $i++;
            echo "Complete ". round($i * 100/$count, 4). "% \n";
        }
    }


    public function cleanData()
    {
        echo "Hello\n";
        $db = \Balon\DB::instance();
        $cond = $db->parentSelect("competition", false, false, false, false, [50000,30000]);
        $result = [];
        foreach ($cond as $key => $val) {
            preg_match_all("/title=\"(.*)\"/Uis", $val['zno'], $array);
            foreach ($array[1] as $k => $v) {
                if (!in_array($v, $result)) {
                    $result[] = $v;
                }
            }
        }
        foreach ($result as $value) {
            $zno = new ZNO();
            $zno->name = $value;
            $db->insert($zno);
        }
        echo "End";
    }
}