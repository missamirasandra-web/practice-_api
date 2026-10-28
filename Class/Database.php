<?php
Class Database {
    protected $conn;
    public function __construct(){
        if (file_exists(__DIR__ . '/../.env')) {
            $config = parse_ini_file(__DIR__ . '/../.env');
        }else{
            $_ENV =$_ENV ?:getenv();
        }

        $_ENV=parse_ini_file(__DIR__ ."/../.env");

        $port=18470;
        $host = $_ENV['DB_HOST'];
        $un = $_ENV['DB_USERNAME'];
        $pass = $_ENV['DB_PASSWORD'];
        $db = $_ENV['DB_DATABASE'];
        
        $cert=__DIR__ . '/ca.pem';
        $this->conn = new mysqli($host, $un, $pass, $db, $port);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function getConnection(): mysqli {
        return $this->conn;
    }
}
?>