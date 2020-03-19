<?php
include 'classes/Config.php';

$host = Config::$sql['host'];
$user = Config::$sql['user'];
$password = Config::$sql['pass'];
$dbname = Config::$sql['name'];
$dsn = 'mysql:host=' . $host . ';dbname=' . $dbname;
$pdo = new PDO($dsn, $user, $password);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

function game_pdo()
{
    $gamehost = Config::$gameSql['host'];
    $gameuser = Config::$gameSql['user'];
    $gamepassword = Config::$gameSql['pass'];
    $gamedbname = Config::$gameSql['name'];
    $gamedsn = 'mysql:host=' . $gamehost . ';dbname=' . $gamedbname . ';charset=utf8';
    static $_PDO = null;

    if ($_PDO === null) {
        $_PDO = new GAMEDB($gamedsn, $gameuser, $gamepassword);
        $_PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    }

    return $_PDO;
}

class GAMEDB extends PDO
{

    protected $_config = [];

    protected $_connected = false;

    public function __construct($dsn, $user = null, $pass = null, $options = null)
    {
        parent::__construct($dsn, $user, $pass, $options);
        //Save connection details for later
        $this->_config = [
            'dsn' => $dsn,
            'user' => $user,
            'pass' => $pass,
            'options' => $options
        ];
    }

    public function checkConnection()
    {
        if (!$this->_connected) {
            parent::__construct($this->_config['dsn'], $this->_config['user'], $this->_config['pass'], $this->_config['options']);
            $this->_connected = true;
        }
    }

    public function query($query, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = [])
    {
        $this->checkConnection();
        return parent::query($query);
    }

    public function prepare($statement, $driver_options = NULL)
    {
        $this->checkConnection();
        return parent::prepare($statement, []);
    }

    public function exec($query)
    {
        $this->checkConnection();
        return parent::exec($query);
    }
}

?>