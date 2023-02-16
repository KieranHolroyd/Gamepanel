<?php
include 'classes/Config.php';

$host = Config::$sql['host'];
$user = Config::$sql['user'];
$password = Config::$sql['pass'];
$dbname = Config::$sql['name'];
$dsn = 'mysql:host=' . $host . ';port=' . Config::$sql['port'] . ';dbname=' . $dbname;
$pdo = new PDO($dsn, $user, $password);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);


class GamePDO extends PDO
{

    protected $_config = [
        'dsn' => "",
        'user' => "",
        'pass' => ""
    ];

    public function __construct()
    {
        try {
            parent::__construct('mysql:host=' . Config::$gameSql['host'] . ';port=' . Config::$sql['port'] . ';dbname=' . Config::$gameSql['name'], Config::$gameSql['user'], Config::$gameSql['pass']);
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();;
        }
    }
}

$gamedb_conn = null;

function game_pdo()
{
    global $gamedb_conn;
    if (Config::$enableGamePanel == true) {
        if ($gamedb_conn == null) {
            $gamedb_conn = new GamePDO();
        }

        return $gamedb_conn;
    }
    return null;
}
