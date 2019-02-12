<?php

class DbConnect
{

    private $server;
    private $dbName;
    private $user;
    private $password;

    public function __construct()
    {
        $config = parse_ini_file('config.ini');
        $this->server = $config['server'];
        $this->dbName = $config['dbName'];
        $this->user = $config['user'];
        $this->password = $config['password'];
    }

    public function connect()
    {
        try {
            $conn = new PDO('mysql:host=' . $this->server . ';dbname=' . $this->dbName,
                $this->user, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (Exception $e) {
            echo "Database error:" . $e->getMessage();
        }
    }
}
