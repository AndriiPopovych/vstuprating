<?php
/**
 * @author balon
 * @copyright 2014
 */
namespace Balon;
/**
 * Class DBProc
 * Класс займається підключенням до бази данних.
 */
class DBProc
{
    private $mysqli;
    //public $pdo;
    public $dev_mod;

    static $stat;

    private function __construct()
    {
        //$this->cache = Cache::instance();
        //$this->replace = 'Запрос выполнен успешно.<script>history.go(-1)</script>';
    }

    function __destruct()
    {
        //echo "Count query to data base = ".$this::$stat;
    }

    function __get($a)
    {
        if ($a == "pdo") {
            return $this->construct();
        }
    }

    private function construct()
    {
        static $host;
        if (!$host) {
            $host = true;
            $db = $this->findConfig();
            $settings = "mysql:host=$db[0];dbname=$db[3];charset=utf8";
            $options = array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            );
            try {
                $this->pdo = new \PDO($settings, $db[1], $db[2], $options);
                return $this->pdo;
            } catch (\PDOException $e) {
                echo $e->getMessage();
            }
        }
    }


    static function instance()
    {
        static $instance;
        if (isset($instance)) {
            return $instance;
        }
        $instance = new static;
        return $instance;
    }


    function send_query($sql, $replace = false)
    {
        self::$stat++;
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute();
            $result = $query->fetchAll();
        } catch (\Exception $e) {
            if (!file_exists("sql_errors.php")) {
                echo " ERROR ".$e->getMessage();
                //file_put_contents("sql_errors.php", time()." ERROR ".$e->getMessage(). "\n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin());
            }
            else {
                $text = file_get_contents("sql_errors.php");
                $text .= time()." ERROR ".$e->getMessage(). "\n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin();
                file_put_contents("sql_errors.php", $text);
            }
            //echo "ERROR" . $e->getMessage();
        }
        return $result;
    }


    /**
     ****function select()****
     * Вхідні данні:
     * @table - назва таблиці, з якої будуть витягуватись данні, без префіксу _table;
     * @what - що буде витягуватись з таблиці. Перерахувати через кому. У разі якщо
     * false, то за умовчуванням ставиться "*"
     * @where - відповідності у рядках переразувати через кому. Приклад: "name = admin;id = 1";
     * @order - по чому здійснювати сортування.
     * @ASC - за умовчуванням приймає значення "DESC";
     * Функція утворює запит до бази данних. Та здійснює його. У разі якщо повертається 1 значення -
     * функція повертає одну змінну, у тому разі, коли повертається декілька значень -
     * функція повертає масив виду $results[0][key] = [value];
     * функція повертає помилку, якщо запит був невдалим.
     **/

    public function select($table, $what = false, $where = Array(), $order = false, $ASC = false, $limit = [])
    {
//        if ($this->cache->get("t_".$table)) {
        if (false) {
            $results = $this->cache->get( $table);
            $results = json_decode($results, true);
            echo $results;
            return $results;
        } else {
            self::$stat++;
            $_SESSION['query'] += 1;
            $dot = explode('.', $table);
            if (count($dot) > 1) {
                $table = "$dot[0]`.`$dot[1]";
            } else {
                $table =  $table;
            }
            $key = $what;
            if (!is_array($what) && !is_object($what)) {
                if (!$what) $what = "*";
                else $what = "`" . $what . "`";
            }
            else {
                $delimiter = "";
                foreach ($what as $whatElem) {
                    if (!$whatElem) $whatText .= $delimiter."*";
                    else $whatText .= $delimiter.$whatElem;
                    $delimiter = ",";
                }
                $what = $whatText;
            }
            $explode = $this->new_explode_where($where);
            $whr = $explode[0];
            if ($where) $where = "WHERE " . $whr;
            if (!$ASC) $ASC = "DESC";
            else $ASC = "ASC";
            if ($order) $order = "ORDER BY `$order` $ASC";
            // ліміт кількості результатів. Якщо немає, то ставить 30
            if ($limit[1]) $limit_text = "LIMIT {$limit[0]},{$limit[1]}";
            else $limit_text = "LIMIT 0,30";
            $sql = "SELECT $what FROM `$table` $where $order $limit_text";
//            echo $sql;
            $query = $this->pdo->prepare($sql);
            $query->execute($explode[1]);
            $res = $query->fetchAll();
            foreach ($res as $row) {
                $results[] = $row;
            }
            if (count($results) > 1 || $what == "*") {
                //$this->cache->setCacheSqlQuery($sql, $table, json_encode($results));
                return $results;
            } else {
                //$this->cache->setCacheSqlQuery($sql, $table, json_encode($results[0][$key]));
                return $results[0][$key];
            }
        }
    }


    /**
     * @param String $table назва таблиці
     * @param int|boolean $parent_id всі значення ( не питай, чого parent_id )
     * @param bool $id повертати знайчення insert_id ( за умовчуванням false )
     * @return mixed|string
     */

    public function insert($table, $parent_id = Array(), $id = false)
    {
        $table =  $table;
        $coma = "";

        $keys = "";
        $values = "";
        if ($parent_id) {
            foreach ($parent_id as $key => $value) {
                $keys .= "$coma `$key`";
                $values .= "$coma ?";
                $coma = ",";
                $exec[] = $value;
            }
        }
        $sql = "INSERT INTO `$table` ($keys) VALUES ($values)";
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }
            $query = $this->pdo->prepare($sql);
            if ($query->execute($exec)) {
                $return_id = $this->pdo->lastInsertId();
            }
        } catch (\PDOException $e) {
            //$this->pdo->rollBack();
            if (!file_exists("sql_errors.php")) {
                echo " ERROR ".$e->getMessage();
                //echo $sql;
                //file_put_contents("sql_errors.php", time()." ERROR ".$e->getMessage(). "\n sql - $sql \n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin());
            }
            else {
                $text = file_get_contents("sql_errors.php");
                $text .= time()." ERROR ".$e->getMessage(). "\n sql - $sql \n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin();
                file_put_contents("sql_errors.php", $text);
            }
            //echo "Error:" . $e->getMessage() . "sql:" . $sql;
        }
        $this->pdo->commit();
        if ($id) {
            return $return_id;
        }
        /*if ($id) {
           if ($this->mysqli->query($sql)) return $this->mysqli->insert_id;
           else return $this->mysqli->error;
        }
        elseif (!$this->mysqli->query($sql)) {
            return $this->mysqli->error;
        }*/
    }


    public function delete($table, $where = false)
    {
        $table =  $table;
        $explode = $this->new_explode_where($where);
        $whr = $explode[0];
        $exec = $explode[1];
        if ($where) $where = "WHERE " . $whr;
        $sql = "DELETE FROM `$table` $where";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute($exec);
        } catch (\PDOException $e) {
            //echo "Error: " . $e->getMessage();
            if (!file_exists("sql_errors.php")) {
                file_put_contents("sql_errors.php", time()." ERROR ".$e->getMessage(). "\n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin());
            }
            else {
                $text = file_get_contents("sql_errors.php");
                $text .= time()." ERROR ".$e->getMessage(). "\n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin();
                file_put_contents("sql_errors.php", $text);
            }
        }
    }

    public function update($table, $values = [], $where = [])
    {
        $table =  $table;
        if ($values) {
            $value = "";
            $and = "";
            foreach ($values as $key => $val) {
                if ($val[0] == "+=") {
                    $value .= "$and `$key` = `$key` + ?";
                    $exec[] = $val[1];
                } elseif ($val[0] == "-=") {
                    $value .= "$and `$key` = `$key` - ?";
                    $exec[] = $val[1];
                } else {
                    $value .= "$and `$key` = ?";
                    $exec[] = $val;
                }
                $and = ",";
            }
        }

        $explode = $this->new_explode_where($where);
        $whr = $explode[0];
        if ($whr) {
            $where = "WHERE $whr";
        }
        $array = $explode[1];
        if (is_array($exec)) {
            $exec = array_merge($exec, $array);
        } else {
            $exec = $array;
        }
        if ($table && $value) {
            $sql = "UPDATE `$table` SET $value $where";
            try {
                $query = $this->pdo->prepare($sql);
                $query->execute($exec);
            } catch (\PDOException $e) {
                if (!file_exists("sql_errors.php")) {
                    //echo time()." ERROR ".$e->getMessage(). "\n sql - $sql \n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin();
                    file_put_contents("sql_errors.php", time()." ERROR ".$e->getMessage(). "\n sql - $sql \n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin());
                }
                else {
                    $text = file_get_contents("sql_errors.php");
                    $text .= time()." ERROR ".$e->getMessage(). "\n sql - $sql \n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin();
                    file_put_contents("sql_errors.php", $text);
//                    echo $text;
                }
                //echo $e->getMessage();
            }
        }
    }

    /**
     * @param array $array
     * @param string $where
     * @return array|string
     */

    public function join($array = Array(), $where = "", $order = Array(), $ASC = false, $limit = "")
    {
        self::$stat++;
        $join = "";
        $on = "";
        $sql = "SELECT * FROM ";
        $last_key = null;
        $joins = "";
        $r = null;
        foreach ($array as $key => $val) {
            //$join .= " $joins `"."t_"."$key` as $key ";
            if ($array[$last_key]) {
                $join .= " $joins `" .  "$key` as $key ON $key.$val = " . $last_key . "." . $array[$last_key];
            } else {
                $join .= " $joins `" .  "$key` as $key ";
            }
            //$on .= "$r $key.$val";
            if (!$r) {
                $r = "=";
                $joins = "RIGHT JOIN";
            }
            $last_key = $key;
        }
        if ($array['article'] && !User::trueAdmin()) $whr = "AND article.`visibility` = 1";
        if ($where) {
            if ($where[2]) {
                $result = " WHERE $where[0].`$where[1]` = ? $whr";
                $where_exec[] = $where[2];
                if ($where[3] && $where[4]) {
                    $result .= " AND $where[3].`$where[4]` = ? $whr";
                    $where_exec[] = $where[5];
                }
            } elseif (is_array($where[0])) {
                $result = " WHERE ";
                foreach ($where[0] as $val) {
                    $result .= " $or $val[0].`$val[1]` = '$val[2]' ";
                    if ($val[3]) $or = $val[3];
                    else $or = " OR ";
                }
            } else {
                $result = " WHERE $where[0] $whr";
                foreach ($where[1] as $val) {
                    $where_exec[] = $val;
                }
            }
        } else if ($array['article'] && !User::trueAdmin()) {
            $result = " WHERE article.`visibility` = 1";
        }
        if (!$ASC) $ASC = "DESC";
        else $ASC = "ASC";
        if ($limit) {
            $limit = " LIMIT $limit[0], $limit[1]";
        }
        if ($order[1]) $order_text = " ORDER BY $order[0].$order[1] $ASC";
        $sql = $sql . "" . $join . "  " . $result . $order_text . $limit;
        try {
            //$result = $this->cache->get($sql);
            if ($results) {
                $result = json_decode($result, true);
            } else {
                $q = $this->pdo->prepare($sql);
                $q->execute($where_exec);
                $result = $q->fetchAll();
                //$this->cache->set($sql);
            }
        } catch (\PDOException $e) {
            if (!file_exists("sql_errors.php")) {
                file_put_contents("sql_errors.php", time()." ERROR ".$e->getMessage(). "\n sql - $sql \n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin());
            }
            else {
                $text = file_get_contents("sql_errors.php");
                $text .= time()." ERROR ".$e->getMessage(). "\n sql - $sql \n user - ". $_COOKIE['user_id']. " trueUser - ". User::trueUser(). " true admin - ".User::trueAdmin();
                file_put_contents("sql_errors.php", $text);
            }
        }
        return $result;
    }

    function explode_where($where)
    {
        if ($where) {
            $where1 = $where;
            $where = explode("||", $where);
            if (count($where) > 1) {
                foreach ($where as $val) {
                    if (preg_match("/.*(!=).*/", $val)) {
                        $array = explode("!=", $val);
                        $array[0] = trim($array[0]);
                        $array[1] = trim($array[1]);
                        $whr .= "$and `$array[0]` != '$array[1]'";
                    } else {
                        $array = explode('=', $val);
                        $array[0] = trim($array[0]);
                        $array[1] = trim($array[1]);
                        $whr .= "$and `$array[0]` = '$array[1]'";
                    }
                    $and = " OR ";
                }
                return $whr;
            }
            $where = explode(';', $where1);
            if (count($where) > 1) {
                foreach ($where as $val) {
                    if (preg_match("/.*(!=).*/", $val)) {
                        $array = explode("!=", $val);
                        $array[0] = trim($array[0]);
                        $array[1] = trim($array[1]);
                        $whr .= "$and `$array[0]` != '$array[1]'";
                    } else {
                        $array = explode('=', $val);
                        $array[0] = trim($array[0]);
                        $array[1] = trim($array[1]);
                        $whr .= "$and `$array[0]` = '$array[1]'";
                    }
                    $and = " AND ";
                }
            } else {
                if (preg_match("/.*(!=).*/", $where[0])) {
                    $array = explode("!=", $where[0]);
                    $array[0] = trim($array[0]);
                    $array[1] = trim($array[1]);
                    $whr .= "`$array[0]` != '$array[1]'";
                } else {
                    $array = explode("=", $where[0]);
                    $array[0] = trim($array[0]);
                    $array[1] = trim($array[1]);
                    $whr .= "`$array[0]` = '$array[1]'";
                }
            }
            return $whr;
        }
    }

    private function new_explode_where($array = Array())
    {
        $result = "";
        $comm = "";
        if (is_array($array) || is_object($array)) {
            foreach ($array as $key => $val) {
                if ($key) {
                    //у випадку join щоб можна було другим параметром задавати OR || AND
                    if (is_array($val)) {
                        if ($val[2]) {
                            $or = $val[2];
                            if (!$val[1]) {
                                $val = $val[0];
                            }
                        }
                    }
                    if ($val[0] == "!=") {
                        $result .= "$comm `$key` != ?";
                        $exec[] = $val[1];
                    } elseif ($val[0] == "||") {
                        $result .= "$comm `$key` || ?";
                        $exec[] = $val[1];
                    } elseif ($val[0] == ">") {
                        $result .= "$comm `$key` > ?";
                        $exec[] = $val[1];
                    } elseif ($val[0] == "<") {
                        $result .= "$comm `$key` < ?";
                        $exec[] = $val[1];
                    } elseif ($val[0] == ">=") {
                        $result .= "$comm `$key` >= ?";
                        $exec[] = $val[1];
                    } elseif ($val[0] == "<=") {
                        $result .= "$comm `$key` <= ?";
                        $exec[] = $val[1];
                    } elseif ($val[0] == "LIKE") {
                        $result .= "$comm `$key` LIKE ?";
                        $exec[] = $val[1];
                    } elseif (is_array($val[0])) {
                        $result .= "$comm `$key` {$val[0][0]} ? {$val[0][2]} `$key` {$val[0][1]} ? ";
                        $exec[] = $val[1][0];
                        $exec[] = $val[1][1];
                    } else {
                        $result .= "$comm `$key` = ?";
                        $exec[] = $val;
                    }
                    if ($or != "OR") {
                        $comm = " AND ";
                    } else {
                        $comm = $val[2];
                    }
                }
            }
        }
        return [$result, $exec];
    }

    private
    function findConfig()
    {
        if (file_exists("../core/config.php")) include("../core/config.php");
        elseif (file_exists("../../core/config.php")) include("../../core/config.php");
        elseif (file_exists("../../../core/config.php")) include("../../../core/config.php");
        elseif (file_exists("config.php")) include("config.php");
        $db = Array($db_host, $db_user, $db_passw, $db_name);
        return $db;
    }

    public
    function db_update()
    {
        if ($this->mysqli) {
            //$this->mysqli->close();
        }
        $this->mysqli = '';
        $part = \Role::getPart();
        $db = $this->findConfig();
        $this->mysqli = new \mysqli("$db[0]", $db[1], "$db[2]", "$db[3]");
        if ($this->mysqli->connect_error) {
            $this->mysqli = new \mysqli("$db[0]", $db[1], "$db[2]", "information_schema");
            if ($this->mysqli->connect_error) {
                echo "У вас проблеми містер";
            }
        }
        if (($part[0][2])) $db = $part[0][2];
        else $db = $db[3];
        $bububu = $db;
        $tables = $this->mysqli->query("SHOW TABLES FROM `$db`");
        if (isset($tables)) {
            $this->mysqli->query("DROP DATABASE `$db`");
        }
        if (file_exists("./db.sql")) {
            $new_db = file_get_contents("./db.sql");
            preg_match_all("|.*База данных:.*`(.*)`.*|", $new_db, $name);
            $name = $name[1][0];
            if (!$name) {
                preg_match_all("|.*База даних:.*`(.*)`.*|", $new_db, $name);
                $name = $name[1][0];
            }
            $this->mysqli->query("CREATE DATABASE `$bububu` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
            echo $name;
            $this->mysqli->close();
            $db = $this->findConfig();
            $this->mysqli = new \mysqli("$db[0]", $db[1], "$db[2]", "$bububu");
            if ($this->mysqli->connect_error) {
                echo "Не вдалося підключитися до створеної бази данних<br>";
            }
            $new_db = preg_replace("|--.*\n|Uis", "", $new_db);
            $new_db = preg_replace("|/\*.*\*/;\n|Uis", "", $new_db);
            $new_db = preg_replace("|.*SET.*time_zone.*;\n|Uis", "", $new_db);
            $new_db = explode(";", $new_db);
            $error = 0;
            $query = 0;
            foreach ($new_db as $key => $val) {
                if ($val) {
                    if (!$this->mysqli->query($val) && $this->mysqli->errno != 1065) {
                        $error++;
                        echo "Помилка №$error " . $this->mysqli->error . "<br>";
                    } else {
                        $query++;
                    }
                }
            }
            if ($query && !$error) {
                echo "Базу синхронізовано без помилок";
            } else {
                echo "Базу синхронізовано з помилками.<br>
                    Кількісь успішних запитів - $query<br>
                    Кількість запитів з помилками - $error";
            }
        }
    }
}


?>