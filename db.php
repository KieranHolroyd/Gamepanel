<?php
include 'classes/Config.php';

$host = Config::$sql['host'];
$user = Config::$sql['user'];
$password = Config::$sql['pass'];
$dbname = Config::$sql['name'];
$dsn = 'mysql:host=' . $host . ';port=' . Config::$sql['port'] . ';dbname=' . $dbname;
$pdo = new PDO($dsn, $user, $password);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);


class GAMEDB extends PDO
{

    protected $_config = [
        'dsn' => "",
        'user' => "",
        'pass' => ""
    ];

    protected $_connected = false;

    public function __construct($dsn = "", $user = "", $pass = "")
    {
        try {
            //Save connection details for later
        
            $this->_config['dsn'] = 'mysql:host=' . Config::$gameSql['host'] . ';port=' . Config::$sql['port'] . ';dbname=' . Config::$gameSql['name'];
            $this->_config['user'] = Config::$gameSql['user'];
            $this->_config['pass'] = Config::$gameSql['pass'];
        

            parent::__construct($this->_config['dsn'], $this->_config['user'], $this->_config['pass']);
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
           
        }
        catch(PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();;
        }  
    }

    public function checkConnection()
    {
        if (!$this->_connected) {
            parent::__construct($this->_config['dsn'], $this->_config['user'], $this->_config['pass']);
            $this->_connected = true;
        }
    }

    public function query(string $query, ?int $mode = PDO::ATTR_DEFAULT_FETCH_MODE, mixed...$fetchModeArgs): PDOStatement|false
    {
        $this->checkConnection();
        return parent::query($query, $mode, ...$fetchModeArgs);
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->checkConnection();
        return parent::prepare($query, $options);
    }

    public function exec(string $query): int|false
    {
        $this->checkConnection();
        return parent::exec($query);
    }
}

function game_pdo()
{
    $gamehost = Config::$gameSql['host'];
    $gameuser = Config::$gameSql['user'];
    $gamepassword = Config::$gameSql['pass'];
    $gamedbname = Config::$gameSql['name'];
    $gamedsn = 'mysql:host=' . $gamehost . ';port=' . Config::$gameSql['port'] . ';dbname=' . $gamedbname . ";charset=utf8";
    static $_PDO = null;

    if ($_PDO === null) {
        $_PDO = new GAMEDB($gamedsn, $gameuser, $gamepassword);
    }

    return $_PDO;
}

game_pdo();
?>